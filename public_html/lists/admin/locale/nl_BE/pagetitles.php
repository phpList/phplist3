<?php
switch ($page) {
  case 'home': $page_title = 'Hoofd Admin Pagina';break;
  case 'setup': $page_title = 'Configuratie Opties';break;
  case 'about': $page_title = 'About '.NAME;break;
  case 'attributes': $page_title = 'Configureer Attributen';break;
  case 'stresstest': $page_title = 'Stress Test';break;
  case 'list': $page_title = 'De Lijst van de Lijsten';break;
  case 'editattributes': $page_title = 'Configureer Attributen';break;
  case 'editlist': $page_title = 'Bewerk een lijst';break;
  case 'checki18n': $page_title = 'Controleer of vertaling bestaat';break;
  case 'import4': $page_title = 'Importeer emails van een extere database';break;
  case 'import3': $page_title = 'Importeer emails van IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Importeer emails';break;
  case 'export': $page_title = 'Exporteer gebruikers';break;
  case 'initialise': $page_title = 'Initialiseer de database';break;
  case 'send': $page_title = 'Verzend een Bericht';break;
  case 'preparesend': $page_title = 'Bereidt een bericht voor om te verzenden';break;
  case 'sendprepared': $page_title = 'Verzend een voorbereid bericht';break;
  case 'members': $page_title = 'Lijst van Leden';break;
  case 'users': $page_title = 'Lijst van alle Gebruikers';break;
  case 'reconcileusers': $page_title = 'Herwerk gebruikers';break;
  case 'user': $page_title = 'Details van een gebruiker';break;
  case 'userhistory': $page_title = 'Geschiedenis van een gebruiker';break;
  case 'messages': $page_title = 'Lijst van beichten';break;
  case 'message': $page_title = 'Bekijk een bericht';break;
  case 'processqueue': $page_title = 'Verzend berichten in wachtrij';break;
  case 'defaults': $page_title = 'Enkele nuttige attributen';break;
  case 'upgrade': $page_title = 'Upgrade '.NAME;break;
  case 'templates': $page_title = 'Templates in het systeem';break;
  case 'template': $page_title = 'Bewerk of voeg een nieuw sjabloon toe';break;
  case 'viewtemplate': $page_title = 'Sjabloon Voorbeeld';break;
  case 'configure': $page_title = 'Configureeer '.NAME;break;
  case 'admin': $page_title = 'Bewerk een Administrator';break;
  case 'admins': $page_title = 'Lijst van Administrators';break;
  case 'adminattributes': $page_title = 'Configureer Administrator attributen';break;
  case 'processbounces': $page_title = 'zoek bounces van de server op';break;
  case 'bounces': $page_title = 'Lijst van bounces';break;
  case 'bounce': $page_title = 'Bekijk een bounce';break;
  case 'spageedit': $page_title = 'Bewerk een inschrijf pagina';break;
  case 'spage': $page_title = 'Inschrijf pagina&acute;s';break;
  case 'eventlog': $page_title = 'Logboek';break;
  case 'getrss': $page_title = 'Zoek RSS feeds op';break;
  case 'viewrss': $page_title = 'Bekijk RSS Items';break;
  case 'community': $page_title = 'Welkom bij de PHPlist community';break;
  case 'vote': $page_title = 'Stem voor PHPlist';break;
  case 'login': $page_title = 'Login';break;
  case 'logout': $page_title = 'Log Uit';break;
  case 'mclicks': $page_title = 'Bericht Klik Statistieken'; break;
  case 'uclicks': $page_title = 'URL Klik Statistieken'; break;
  case 'massunconfirm': $page_title = 'Massa Onbevestigde emails';break;
}
?>
