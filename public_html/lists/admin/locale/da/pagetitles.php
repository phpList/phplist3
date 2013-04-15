<?php
switch ($page) {
  case 'home': $page_title = 'Hoved admin side';break;
  case 'setup': $page_title = 'Konfigurations valg';break;
  case 'about': $page_title = 'Om '.NAME;break;
  case 'attributes': $page_title = 'Konfigurer attributter';break;
  case 'stresstest': $page_title = 'Stress test';break;
  case 'list': $page_title = 'Liste over lister';break;
  case 'editattributes': $page_title = 'CKnfigurer attributter';break;
  case 'editlist': $page_title = 'Rediger en liste';break;
  case 'checki18n': $page_title = 'Tjek om overs&aelig;ttelse findes';break;
  case 'import4': $page_title = 'Importer emails fra remote database';break;
  case 'import3': $page_title = 'Importer emails fra IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Importer emails';break;
  case 'export': $page_title = 'Eksporter brugere';break;
  case 'initialise': $page_title = 'Initialiser database';break;
  case 'send': $page_title = 'Send en besked';break;
  case 'preparesend': $page_title = 'Forbered besked til afsendelse';break;
  case 'sendprepared': $page_title = 'Send en forberedt besked';break;
  case 'members': $page_title = 'Vis medlemsskab';break;
  case 'users': $page_title = 'Vis alle brugere';break;
  case 'reconcileusers': $page_title = 'Genopfrisk brugere';break;
  case 'user': $page_title = 'Bruger detaljer';break;
  case 'userhistory': $page_title = 'Bruger historik';break;
  case 'messages': $page_title = 'liste med beskeder';break;
  case 'message': $page_title = 'Se en besked';break;
  case 'processqueue': $page_title = 'Send besked k&oslash;';break;
  case 'defaults': $page_title = 'Nogle brugbare standard attributter';break;
  case 'upgrade': $page_title = 'Opgrader '.NAME;break;
  case 'templates': $page_title = 'Skabeloner i systemet';break;
  case 'template': $page_title = 'Tilf&oslash;j eller rediger en skabelon';break;
  case 'viewtemplate': $page_title = 'Se skabelon';break;
  case 'configure': $page_title = 'Konfigurer '.NAME;break;
  case 'admin': $page_title = 'Rediger en administrator';break;
  case 'admins': $page_title = 'Vis administratorer';break;
  case 'adminattributes': $page_title = 'Konfigurer administrator attributter';break;
  case 'processbounces': $page_title = 'Hent bounces fra server';break;
  case 'bounces': $page_title = 'Vis bounces';break;
  case 'bounce': $page_title = 'Se en bounce';break;
  case 'spageedit': $page_title = 'Rediger en tilmeldings side';break;
  case 'spage': $page_title = 'Tilmeldings sider';break;
  case 'eventlog': $page_title = 'H&aelig;ndelses log';break;
  case 'getrss': $page_title = 'Hent RSS feeds';break;
  case 'viewrss': $page_title = 'Se RSS emner';break;
  case 'community': $page_title = 'Velkommen til PHPlist f&aelig;llesskabet';break;
  case 'vote': $page_title = 'Stem p&aring; PHPlist';break;
  case 'login': $page_title = 'Log ind';break;
  case 'logout': $page_title = 'Log ud';break;
  case 'mclicks': $page_title = 'Besked klik statistik'; break;
  case 'uclicks': $page_title = 'URL klik statistik'; break;
  case 'massunconfirm': $page_title = 'Masse ikke-bekr&aelig;ft emails';break;
}
?>
