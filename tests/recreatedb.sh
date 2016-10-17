#!/bin/bash

## script to drop and recreate the DB, so that each test starts from scratch
 
mysqladmin -uroot -pXXXXX drop -f phplisttraviscidb && mysqladmin -uroot -XXXXX create phplisttraviscidb
