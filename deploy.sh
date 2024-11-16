#!/bin/bash

# compilation hypervisor.c
gcc -g -Wall hypervisor.c -o hypervisor -lvirt

# lancement du serveur php
php -S localhost:8000
