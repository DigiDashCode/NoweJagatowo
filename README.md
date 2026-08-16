# NoweJagatowo
1. Download XAMP and install it: https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download

2. Start the panel. Start Apache and MySQL

3. Clone repository to XAMP-folder /htdocs/NoweJagatowo
https://github.com/DigiDashCode/NoweJagatowo.git

4. Setup database: in XAMPP dashboard click admin next to MySQL. 
- If first time setup: click on SQL paste this script and press GO: `CREATE DATABASE house_sale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
- Click "Import" select setup.sql file in /htdocs/NoweJagatowo

5. To open website type in browser localhost/NoweJagatowo/

Admin page address is: http://localhost/NoweJagatowo/admin.php