#!/bin/bash
os=$(uname)
install_pgsql=Y
install_sqlite=Y
install_mysql=Y

read -p "Install php-sqlite? (Y/n)" install_sqlite
read -p "Install php-pgsql? (Y/n)" install_pgsql
read -p "Install php-mysql? (Y/n)" install_mysql

if [[ $os == "Darwin" ]] ; then
    port=$(which port)
    brew=$(which brew)
    if [[ -e $port ]] ; then
        $port install php5-yaml
        $port install php5-apc

        if [[ $install_mysql != 'n' ]]  ; then $port install php5-mysql      ; fi
        if [[ $install_sqlite != 'n' ]] ; then $port install php5-sqlite     ; fi
        if [[ $install_pgsql != 'n' ]]  ; then $port install php5-postgresql ; fi
    elif [[ -e $brew ]] ; then
        echo "brew install: not supported yet"
    fi
elif [[ $os == "Linux" ]] ; then
    apt=$(which apt-get)
    if [[ -e $apt ]] ; then
        $apt install -y php5-dev
        $apt install -y php5-cli
        $apt install -y php-apc
        if [[ $install_mysql != 'n' ]]  ; then $apt install -y php5-mysql      ; fi
        if [[ $install_sqlite != 'n' ]] ; then $apt install -y php5-sqlite     ; fi
        if [[ $install_pgsql != 'n' ]]  ; then $apt install -y php5-postgresql ; fi
    fi
fi

mkdir /tmp
pecl install yaml
pear channel-discover pear.corneltek.com
git clone https://c9s@github.com/c9s/LazyRecord.git
cd LazyRecord
pear install -f package.xml
