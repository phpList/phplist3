<?php
switch ($page) {
  case 'home': $page_title = 'Adminisztráció főlap';break;
  case 'setup': $page_title = 'Beállítási lehetőségek';break;
  case 'about': $page_title = 'A '.NAME.' névjegye';break;
  case 'attributes': $page_title = 'Tulajdonságok beállítása';break;
  case 'stresstest': $page_title = 'Terhelési próba';break;
  case 'list': $page_title = 'A listák listája';break;
  case 'editattributes': $page_title = 'Tulajdonságok beállítása';break;
  case 'editlist': $page_title = 'Lista módosítása';break;
  case 'checki18n': $page_title = 'Fordítások meglétének ellenőrzése';break;
  case 'import4': $page_title = 'E-mail címek importálása távoli adatbázisból';break;
  case 'import3': $page_title = 'E-mail címek importálása IMAP kiszolgálóról';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'E-mail címek importálása';break;
  case 'export': $page_title = 'Felhasználók exportálása';break;
  case 'initialise': $page_title = 'Adatbázis inicializálása';break;
  case 'send': $page_title = 'Üzenet küldése';break;
  case 'preparesend': $page_title = 'Üzenet előkészítése küldésre';break;
  case 'sendprepared': $page_title = 'Előkészített üzenet küldése';break;
  case 'members': $page_title = 'Lista tagjai';break;
  case 'users': $page_title = 'Az összes felhasználó listája';break;
  case 'reconcileusers': $page_title = 'Felhasználók újraegyeztetése';break;
  case 'user': $page_title = 'Felhasználó adatai';break;
  case 'userhistory': $page_title = 'Felhasználó előzményei';break;
  case 'messages': $page_title = 'üzenetlista';break;
  case 'message': $page_title = 'Üzenet megtekintése';break;
  case 'processqueue': $page_title = 'Üzenet-várólista küldése';break;
  case 'defaults': $page_title = 'Néhány hasznos alapértelmezett tulajdonság';break;
  case 'upgrade': $page_title = 'A '.NAME.' frissítése';break;
  case 'templates': $page_title = 'A rendszerben lévő sablonok';break;
  case 'template': $page_title = 'Sablon hozzáadása vagy szerkesztése';break;
  case 'viewtemplate': $page_title = 'Sablon előnézete';break;
  case 'configure': $page_title = 'A '.NAME.' beállítása';break;
  case 'admin': $page_title = 'Adminisztrátor módosítása';break;
  case 'admins': $page_title = 'Adminisztrátorok listája';break;
  case 'adminattributes': $page_title = 'Adminisztrátor tulajdonságainak beállítása';break;
  case 'processbounces': $page_title = 'Visszapattanók lekérése a kiszolgálóról';break;
  case 'bounces': $page_title = 'Visszapattanók listája';break;
  case 'bounce': $page_title = 'Visszapattanó megtekintése';break;
  case 'spageedit': $page_title = 'Rendelési oldal szerkesztése';break;
  case 'spage': $page_title = 'Rendelési oldalak';break;
  case 'eventlog': $page_title = 'Eseménynapló';break;
  case 'getrss': $page_title = 'RSS-csatornák lekérése';break;
  case 'viewrss': $page_title = 'RSS-elemek megtekintése';break;
  case 'community': $page_title = 'Üdvözöljük a PHPlist közösségében';break;
  case 'vote': $page_title = 'Szavazzon a PHPlistre';break;
  case 'login': $page_title = 'Bejelentkezés';break;
  case 'logout': $page_title = 'Kijelentkezés';break;
  case 'mclicks': $page_title = 'Üzenetkattintási statisztika'; break;
  case 'uclicks': $page_title = 'URL-kattintási statisztika'; break;
  case 'massunconfirm': $page_title = 'E-mail címek visszaigazolásának tömeges megszüntetése';break;
}
?>
