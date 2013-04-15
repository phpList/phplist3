<?php
switch ($page) {
  case 'home': $page_title = 'Startsidan för administratörer';break;
  case 'setup': $page_title = 'Konfigurationsalternativ';break;
  case 'about': $page_title = 'Om '.NAME;break;
  case 'attributes': $page_title = 'Konfigurera attribut';break;
  case 'stresstest': $page_title = 'Stresstest';break;
  case 'list': $page_title = 'Listade listor';break;
  case 'editattributes': $page_title = 'Konfigurera attribut';break;
  case 'editlist': $page_title = 'Redigera en lista';break;
  case 'checki18n': $page_title = 'Kolla att översättningar finns';break;
  case 'import4': $page_title = 'Importera e-postadresser från en extern databas';break;
  case 'import3': $page_title = 'Importera e-postadresser från IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Importera e-postadresser';break;
  case 'export': $page_title = 'Exportera medlemmar';break;
  case 'initialise': $page_title = 'Initialisera databasen';break;
  case 'send': $page_title = 'Sänd ett utskick';break;
  case 'preparesend': $page_title = 'Förbered ett utskick för sändning';break;
  case 'sendprepared': $page_title = 'Sänd ett förberett utskick';break;
  case 'members': $page_title = 'Listmedlemskap';break;
  case 'users': $page_title = 'Lista över alla medlemmar';break;
  case 'reconcileusers': $page_title = 'Stäm av medlemmar';break;
  case 'user': $page_title = 'Meldmesdetaljer';break;
  case 'userhistory': $page_title = 'Medlemshistorik';break;
  case 'messages': $page_title = 'lista över utskick';break;
  case 'message': $page_title = 'Visa ett utskick';break;
  case 'processqueue': $page_title = 'Sänd utskickskö';break;
  case 'defaults': $page_title = 'Några användbara standardattribut';break;
  case 'upgrade': $page_title = 'Uppgradera '.NAME;break;
  case 'templates': $page_title = 'Mallar i systemet';break;
  case 'template': $page_title = 'Lägg till eller redigera en mall';break;
  case 'viewtemplate': $page_title = 'Mallförhandsgranskning';break;
  case 'configure': $page_title = 'Konfigurera '.NAME;break;
  case 'admin': $page_title = 'Redigera en administratör';break;
  case 'admins': $page_title = 'Lista administratörer';break;
  case 'adminattributes': $page_title = 'Konfigurera administratörattribut';break;
  case 'processbounces': $page_title = 'Hämta tillbaks avvisade utskick från server';break;
  case 'bounces': $page_title = 'Lista avvisade utskick';break;
  case 'bounce': $page_title = 'Visa ett avvisat utskick';break;
  case 'spageedit': $page_title = 'Redigera en anmälningssida';break;
  case 'spage': $page_title = 'Anmälningssidor';break;
  case 'eventlog': $page_title = 'Händelselogg';break;
  case 'getrss': $page_title = 'Hämta tillbaks RSS-feeds';break;
  case 'viewrss': $page_title = 'Visa RSS-poster';break;
  case 'community': $page_title = 'Välkommen till PHPlist-gemenskapen';break;
  case 'vote': $page_title = 'Rösta på PHPlist';break;
  case 'login': $page_title = 'Logga in';break;
  case 'logout': $page_title = 'Logga ut';break;
  case 'mclicks': $page_title = 'Utskicksklickstatistik'; break;
  case 'uclicks': $page_title = 'URL-klickstatistik'; break;
  case 'massunconfirm': $page_title = 'Massavbekräfta e-postadresser';break;
}
?>
