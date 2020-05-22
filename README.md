
  
# SimpleMenu  
  
SimpleMenu je webová aplikace usnadňující tvorbu menu restaurací.  
  
## Programy pro zprovoznění offline  
XAMPP (vyvíjeno s verzí [7.4.1](https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/7.4.1/))  
https://www.apachefriends.org/download.html  
  
Symfony CLI (vyvíjeno s verzí [4.15.0](https://github.com/symfony/cli/releases/download/v4.15.0/symfony_windows_amd64.exe))  
https://symfony.com/download  
  
Composer (vyvíjeno s verzí 1.9.2)  
https://getcomposer.org/download/  
  
GIT (vyvíjeno s verzí 2.25.0.windows.1)  
https://git-scm.com/download/win  
  
## Instalace  
Návod na [stránkách  Symfony](https://symfony.com/doc/current/setup.html#setting-up-an-existing-symfony-project).   
  
Všechny příkazy je nutné zadávat ve složce projektu.  
### Instalace závislostí  
```bash  
composer install  
```  
### Databáze  
V souboru `.env` je uloženo nastavení přístupovách práv a název databáze, kterou Symfony využívá. Aktuální nastavení:  
```  
DATABASE_URL=mysql://bp02:M3nicka@localhost:3306/bp02  
```  
Je nutné buď vytvořit uživatele `bp02` s heslem `M3nicka` nebo změnit nastavení v souboru `.env`.  
  
Smazání databáze:  
```  
php bin/console doctrine:database:drop --force  
```  
Vytvoření databáze:  
```  
php bin/console doctrine:database:create  
```  
Aktualizace schématu databáze:  
```  
php bin/console doctrine:schema:update --force  
```  
  
#### Vložení výchozích hodnot do databáze  
Pro vložení hodnot je nejsnažší využít PHPMyAdmin. Nejprve je nutné spustit Apache a MySQL server v kontrolním panelu XAMPP. Výchozí cesta k ovládacímu panelu aplikace XAMPP: `C:\xampp\xampp-control.exe`. Poté je na adrese [http://localhost/phpmyadmin](http://localhost/phpmyadmin) dostupný PHPMyAdmin.
  
SQL pro vložení výchozích hodnot do tabulky `skin`:  
```sql  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `simplex`);  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `journal`);  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `sandstone`);  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `litera`);  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `united`);  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `spacelab`);  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `minty`);  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `cosmo`);  
INSERT INTO `skin` (`id`, `name`) VALUES (NULL, `cerulean`);  
```  
  
## Spuštení  
Pro běh aplikace je nutné mít spuštěný MySQL server. Ten lze spustit v aplikaci XAMPP. Výchozí cesta k ovládacímu panelu aplikace XAMPP: `C:\xampp\xampp-control.exe`.  
  
Spuštení aplikace:  
```bash  
symfony server:start  
```  
Poté je aplikace dostupná na [http://localhost:8000/](http://localhost:8000/).  
  
## Admin aplikace  
Pro zvýšení práv uživatele na administrátorská je třeba přímo v databázi změnit roli daného uživatele v tabulce `user` na `USER_ADMIN`.