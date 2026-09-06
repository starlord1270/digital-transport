#!/usr/bin/env bash

# DIGITAL TRANSPORT - SCRIPT DE PROVISIONAMIENTO DE BASE DE DATOS DE PRODUCCIÓN
echo "====================================================="
echo " Ejecutando Aprovisionamiento de Base de Datos"
echo "====================================================="

php backend/setup_database.php
