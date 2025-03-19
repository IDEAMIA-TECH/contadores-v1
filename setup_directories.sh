#!/bin/bash

# Crear estructura de directorios principal
mkdir -p public
mkdir -p app/{config,controllers,models,views,helpers,middleware}
mkdir -p app/views/{layouts,accountants,clients,auth}
mkdir -p public/{css,js,uploads/xml}
mkdir -p database 