<?php
switch ($page) {
  case 'home': $page_title = 'Hauptseite';break;
  case 'setup': $page_title = 'Setup-Assistent';break;
  case 'about': $page_title = '&Uuml;ber '.NAME;break;
  case 'attributes': $page_title = 'Attribute f&uuml;r Abonnenten';break;
  case 'stresstest': $page_title = 'Stress-Test';break;
  case 'list': $page_title = 'Listen';break;
  case 'editattributes': $page_title = 'Attribute';break;
  case 'editlist': $page_title = 'Listen-Details';break;
  case 'checki18n': $page_title = '&Uuml;berpr&uuml;fung ob &Uuml;bersetzung existiert';break;
  case 'import4': $page_title = 'Abonnenten-Import aus Datenbank';break;
  case 'import3': $page_title = 'Abonnenten-Import aus IMAP-Konto';break;
  case 'import2': $page_title = 'Abonnenten-Import (abweichende Attribute)';break;
  case 'import1': $page_title = 'Abonnenten-Import (&uuml;bereinstimmende Attribute)';break;
  case 'import': $page_title = 'Abonnenten-Import';break;
  case 'export': $page_title = 'Abonnenten-Export';break;
  case 'initialise': $page_title = 'Datenbank-Initialisierung';break;
  case 'send': $page_title = 'Nachricht erstellen/bearbeiten';break;
  case 'preparesend': $page_title = 'Vorlage erstellen/bearbeiten';break;
  case 'sendprepared': $page_title = 'Neue Nachricht ab Vorlage';break;
  case 'members': $page_title = 'Listen-Abonnenten';break;
  case 'users': $page_title = 'Abonnenten';break;
  case 'reconcileusers': $page_title = 'Abonnenten bereinigen';break;
  case 'user': $page_title = 'Abonnenten-Detailinformationen';break;
  case 'userhistory': $page_title = 'Abonnenten-History';break;
  case 'messages': $page_title = 'Nachrichten';break;
  case 'message': $page_title = 'Nachrichten-Detailinformationen';break;
  case 'processqueue': $page_title = 'Warteschlange';break;
  case 'defaults': $page_title = 'Vordefinierte Attribute';break;
  case 'upgrade': $page_title = 'Datenbank-Upgrade';break;
  case 'templates': $page_title = 'Templates';break;
  case 'template': $page_title = 'Template erstellen/bearbeiten';break;
  case 'viewtemplate': $page_title = 'Template-Vorschau';break;
  case 'configure': $page_title = 'Konfiguration';break;
  case 'admin': $page_title = 'Administrator erstellen/bearbeiten';break;
  case 'admins': $page_title = 'Administratoren';break;
  case 'adminattributes': $page_title = 'Attribute f&uuml;r Administratoren';break;
  case 'processbounces': $page_title = 'Retouren abholen';break;
  case 'bounces': $page_title = 'Retouren verarbeiten';break;
  case 'bounce': $page_title = 'Nicht zustellbare Mail anzeigen';break;
  case 'spageedit': $page_title = 'Anmeldeseite erstellen/bearbeiten';break;
  case 'spage': $page_title = 'Anmeldeseiten';break;
  case 'eventlog': $page_title = 'Event-Log';break;
  case 'getrss': $page_title = 'RSS-Meldungen importieren';break;
  case 'viewrss': $page_title = 'RSS-Meldungen anzeigen';break;
  case 'community': $page_title = 'Hilfe';break;
  case 'vote': $page_title = 'PHPlist bewerten';break;
  case 'login': $page_title = 'Login';break;
  case 'logout': $page_title = 'Logout';break;
  case 'mclicks': $page_title = 'Statistik: Klicks pro Nachricht'; break;
  case 'uclicks': $page_title = 'Statistik: Klicks pro URL'; break;
  case 'massunconfirm': $page_title = 'Mass Unconfirm emails';break;

  // the following page titles are missing in the english language file
  case 'dbcheck': $page_title = 'DB-Check'; break;
  case 'statsmgt': $page_title = 'Statistik'; break;
  case 'mviews': $page_title = 'Statistik: Ge&ouml;ffnete Mails'; break;
  case 'domainstats': $page_title = 'Domain-Statistik'; break;
  case 'usermgt': $page_title = 'Abonnentenverwaltung'; break;
  case 'usercheck': $page_title = 'Abonnenten pr&uuml;fen'; break;
	case 'purgerss': $page_title = 'RSS-Meldungen l&ouml;schen';break;
	case 'updatetranslation': $page_title = 'Übersetzung neu laden';break;
}
?>
