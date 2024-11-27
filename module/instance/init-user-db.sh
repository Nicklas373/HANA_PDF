#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE hanaci_pdf;
    CREATE USER eureka WITH PASSWORD 'eureka';
    GRANT USAGE, CREATE ON SCHEMA public TO eureka;
    ANAYLYZE;
EOSQL
