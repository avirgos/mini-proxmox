#include <stdio.h>
#include <stdlib.h>
#include <libvirt/libvirt.h>
#include <string.h>

// Connect to hypervisor
virConnectPtr connectToHypervisor(const char *remoteHost) {
    virConnectPtr conn;

    char uri[256];
    snprintf(uri, sizeof(uri), "qemu+ssh://%s/system", remoteHost);

    conn = virConnectOpen(uri); 
    if (conn == NULL) {
        fprintf(stderr, "Failed to open connection to %s\n", uri);
        return NULL;
    }

    printf("Connection successful to %s\n", uri);
    return conn;
}

// Print connection details
void printConnectionDetails(virConnectPtr conn) {
    char *host = virConnectGetHostname(conn);
    fprintf(stdout, "  \"hostname\": \"%s\",\n", host);
    free(host);
    fprintf(stdout, "  \"encrypted\": %d,\n", virConnectIsEncrypted(conn));
    
    int vcpus = virConnectGetMaxVcpus(conn, NULL);
    fprintf(stdout, "  \"max_vcpus\": %d,\n", vcpus);
    
    unsigned long long node_free_memory = virNodeGetFreeMemory(conn);
    fprintf(stdout, "  \"free_memory_gb\": %.2f\n", node_free_memory / (1024.0 * 1024 * 1024));
}

// List active domains
void listActiveDomainsJSON(virConnectPtr conn) {
    int numActiveDomains = virConnectNumOfDomains(conn);
    int *activeDomains = malloc(sizeof(int) * numActiveDomains);

    numActiveDomains = virConnectListDomains(conn, activeDomains, numActiveDomains);

    printf("  \"active_domains\": [\n");
    for (int i = 0; i < numActiveDomains; ++i) {
        virDomainPtr domain = virDomainLookupByID(conn, activeDomains[i]);

        if (domain != NULL) {
            virDomainInfo info;
            virDomainGetInfo(domain, &info);
            const char *name = virDomainGetName(domain);

            printf("    {\n");
            printf("      \"id\": %d,\n", activeDomains[i]);
            printf("      \"name\": \"%s\",\n", name);
            printf("      \"state\": %d,\n", info.state);
            printf("      \"maxMem\": %lu,\n", info.maxMem);
            printf("      \"memory\": %lu,\n", info.memory);
            printf("      \"nrVirtCpu\": %d,\n", info.nrVirtCpu);
            printf("      \"cpuTime\": %llu\n", info.cpuTime);
            printf("    }%s\n", i < numActiveDomains - 1 ? "," : "");

            virDomainFree(domain);
        }
    }
    printf("  ],\n");

    free(activeDomains);
}

// List inactive domains
void listInactiveDomainsJSON(virConnectPtr conn) {
    int numInactiveDomains = virConnectNumOfDefinedDomains(conn);
    char **inactiveDomains = malloc(sizeof(char *) * numInactiveDomains);

    numInactiveDomains = virConnectListDefinedDomains(conn, inactiveDomains, numInactiveDomains);
    
    printf("  \"inactive_domains\": [\n");
    for (int i = 0; i < numInactiveDomains; ++i) {
        printf("    {\n");
        printf("      \"name\": \"%s\"\n", inactiveDomains[i]);
        printf("    }%s\n", i < numInactiveDomains - 1 ? "," : "");
        free(inactiveDomains[i]);
    }
    printf("  ]\n");

    free(inactiveDomains);
}

// Create domain
int startDomain(virConnectPtr conn, const char *vmName) {
    virDomainPtr dom = virDomainLookupByName(conn, vmName);
    if (!dom) {
        fprintf(stderr, "Unable to find guest configuration\n");
        return -1;
    } 
    if (virDomainCreate(dom) < 0) {
        fprintf(stderr, "Unable to boot guest\n");
        virDomainFree(dom);
        return -1;
    }

    fprintf(stdout, "	\"message\": \"La VM \\\"%s\\\" est démarrée.\",\n", vmName);
    virDomainFree(dom);
    return 0;
}

// Stop domain
int stopDomain(virConnectPtr conn, const char *vmName) {
    virDomainPtr dom = virDomainLookupByName(conn, vmName);
    if (!dom) {
        fprintf(stderr, "Unable to find guest configuration\n");
        return -1;
    } 
    if (virDomainDestroy(dom) < 0) {
        fprintf(stderr, "Unable to destroy the guest\n");
        virDomainFree(dom);
        return -1;
    }

    fprintf(stdout, "   \"message\": \"La VM \\\"%s\\\" est stoppée.\",\n", vmName);
    virDomainFree(dom);
    return 0;
}

// Save domain state
int saveDomainState(virConnectPtr conn, const char *vmName) {
    virDomainPtr dom = virDomainLookupByName(conn, vmName);
    
    const char *basePath = "/var/lib/libvirt/qemu/save/";
    char filename[512];
    
    snprintf(filename, sizeof(filename), "%s%s.img", basePath, vmName);

    if (!dom) {
        fprintf(stderr, "Cannot find guest to be saved\n");
        return -1;
    }
    if (virDomainSave(dom, filename) < 0) {
        fprintf(stderr, "Unable to save guest to %s\n", filename);
        virDomainFree(dom);
        return -1;
    }
    
    fprintf(stdout, "	\"message\": \"VM sauvegardée dans \\\"%s\\\".\",\n", filename);
    virDomainFree(dom);
    return 0;
}
    
// Restore domain state 
int restoreDomainState(virConnectPtr conn, const char *vmName) {
    const char *basePath = "/var/lib/libvirt/qemu/save/";
    char filename[512];

    snprintf(filename, sizeof(filename), "%s%s.img", basePath, vmName);

    if (virDomainRestore(conn, filename) < 0) {
        fprintf(stderr, "Unable to restore guest from %s\n", filename);
        return -1;
    }

    fprintf(stdout, "Guest state restored from %s\n", filename);
    return 0;
}

// Main program
int main(int argc, char *argv[]) {
    if (argc < 3 || argc > 4) {
        fprintf(stderr, "Usage: %s <remote-host> <option> [<vm-name>]\n", argv[0]);
        return 1;
    }

    const char *remoteHost = argv[1];
    const char *option = argv[2];
    const char *vmName = NULL;

    if (argc == 4) {
        vmName = argv[3];
    }


    virConnectPtr conn = connectToHypervisor(remoteHost);
    if (conn == NULL)
        return 1;

    printf("{\n");

    if (strcmp(option, "l") == 0) {
        listActiveDomainsJSON(conn);
        listInactiveDomainsJSON(conn);
    } else if (strcmp(option, "c") == 0) {
        if (!vmName) {
            // Pas de nom fourni, demander via stdin
            char inputName[256];
            printf("Enter the VM name: ");
            fflush(stdout);
            fgets(inputName, sizeof(inputName), stdin);
            inputName[strcspn(inputName, "\n")] = 0; // Remove newline character
            vmName = strdup(inputName); // Allocate memory for vmName
        }

        printf("  \"action\": \"start\",\n");
        printf("  \"vm_name\": \"%s\",\n", vmName);
        int result = startDomain(conn, vmName);
        printf("  \"result\": %d\n", result);
    
	if (!argv[3]) {
	    free((void *) vmName);
	}
    } else if (strcmp(option, "d") == 0) {
        if (!vmName) {
            // Pas de nom fourni, demander via stdin
            char inputName[256];
            printf("Enter the VM name: ");
            fflush(stdout);
            fgets(inputName, sizeof(inputName), stdin);
            inputName[strcspn(inputName, "\n")] = 0; // Remove newline character
            
	    vmName = strdup(inputName); // Allocate memory for vmName
        }

        printf("  \"action\": \"stop\",\n");
        printf("  \"vm_name\": \"%s\",\n", vmName);
        int result = stopDomain(conn, vmName);
        printf("  \"result\": %d\n", result);

	if (!argv[3]) {
            free((void *) vmName);
	}
    } else if (strcmp(option, "s") == 0) {
        if (!vmName) {
            // Pas de nom fourni, demander via stdin
            char inputName[256];
            printf("Enter the VM name: ");
            fflush(stdout);
            fgets(inputName, sizeof(inputName), stdin);
            inputName[strcspn(inputName, "\n")] = 0; // Remove newline character
            
	    vmName = strdup(inputName); // Allocate memory for vmName
        }

        printf("  \"action\": \"save\",\n");
        printf("  \"vm_name\": \"%s\",\n", vmName);
        int result = saveDomainState(conn, vmName);
        printf("  \"result\": %d\n", result);

	if (!argv[3]) {
            free((void *) vmName);
	}   
    } else if (strcmp(option, "r") == 0) {
        char vmName[256];
        printf("  \"action\": \"restore\",\n");
        printf("  \"vm_name\": \"");
        fflush(stdout);
        fgets(vmName, sizeof(vmName), stdin);
        vmName[strcspn(vmName, "\n")] = 0;
        printf("%s\",\n", vmName);
        printf("  \"result\": %d\n", restoreDomainState(conn, vmName));
    } else if (strcmp(option, "e") == 0) {
        printf("  \"message\": \"Déconnexion\"\n");
    } else {
        printf("  \"error\": \"Commande inconnue : %s\"\n", option);
    }

    printf("}\n");

    virConnectClose(conn);
    return 0;
}
