# Create Installable
To Make an installable package that can be used in Wordpress directly, a composer script was created, that installs all dependencies and deletes all development files from the repository as well as unnecessary doc files.

## prerequisits

- make sure you are using the target php version before running the script
- script runs with [composer](https://getcomposer.org/)

## Steps 

1. clone repository
2. use target php version (>=7.3)
3. run script `composer run-script create-installable`

