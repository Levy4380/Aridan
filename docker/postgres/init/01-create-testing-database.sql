-- Separate database for the Pest test suite.
CREATE DATABASE aridan_testing OWNER aridan;

-- btree_gist backs the reservations overlap exclusion constraint.
\connect aridan
CREATE EXTENSION IF NOT EXISTS btree_gist;
\connect aridan_testing
CREATE EXTENSION IF NOT EXISTS btree_gist;
