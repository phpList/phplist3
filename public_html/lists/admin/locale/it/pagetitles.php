<?php
switch ($page) {
  case 'home': $page_title = 'Pagina principale Amministrazione';break;
  case 'setup': $page_title = 'Opzioni di Configurazione';break;
  case 'about': $page_title = 'Riguardo a '.NAME;break;
  case 'attributes': $page_title = 'Configura Attributi';break;
  case 'stresstest': $page_title = 'Test di Stress';break;
  case 'list': $page_title = 'La lista delle liste';break;
  case 'editattributes': $page_title = 'Configura Attributi';break;
  case 'editlist': $page_title = 'Modifica una lista';break;
  case 'checki18n': $page_title = 'Controlla che la traduzione esista';break;
  case 'import4': $page_title = 'Importa emails da un database remoto';break;
  case 'import3': $page_title = 'Importa emails da IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Importa emails';break;
  case 'export': $page_title = 'Esporta utenti';break;
  case 'initialise': $page_title = 'Inizializza il database';break;
  case 'send': $page_title = 'Spedisci un Messaggio';break;
  case 'preparesend': $page_title = 'Prepare un messaggio per spedirlo';break;
  case 'sendprepared': $page_title = 'Spedisci un messaggio preparato';break;
  case 'members': $page_title = 'Elenco soci membri';break;
  case 'users': $page_title = 'Elenca tutti gli utenti';break;
  case 'reconcileusers': $page_title = 'Reconcile users';break;
  case 'user': $page_title = 'Dettagli di un utente';break;
  case 'userhistory': $page_title = 'Storico di un utente';break;
  case 'messages': $page_title = 'Elenco messaggi';break;
  case 'message': $page_title = 'Visualizza un messaggio';break;
  case 'processqueue': $page_title = 'Spedisci i messaggi in coda';break;
  case 'defaults': $page_title = 'Alcuni pratici attributi di default';break;
  case 'upgrade': $page_title = 'Aggiorna '.NAME;break;
  case 'templates': $page_title = 'Modelli nel sistema';break;
  case 'template': $page_title = 'Aggiunge o Modifica un modello';break;
  case 'viewtemplate': $page_title = 'Anteprima Modello';break;
  case 'configure': $page_title = 'Configura '.NAME;break;
  case 'admin': $page_title = 'Modifica amministratore';break;
  case 'admins': $page_title = 'Elenco amministratori';break;
  case 'adminattributes': $page_title = 'Configura attributi degli Amministratori';break;
  case 'processbounces': $page_title = 'Recupera respinti dal server';break;
  case 'bounces': $page_title = 'Elenco respinti';break;
  case 'bounce': $page_title = 'Visualizza un respinto';break;
  case 'spageedit': $page_title = 'Modifica una pagina di sottoscrizione';break;
  case 'spage': $page_title = 'Pagine di Sottoscrizione';break;
  case 'eventlog': $page_title = 'Log degli eventi';break;
  case 'getrss': $page_title = 'Recupera RSS feeds';break;
  case 'viewrss': $page_title = 'Visualizza RSS Items';break;
  case 'community': $page_title = 'Benvenuti alla comunità di PHPlist';break;
  case 'vote': $page_title = 'Vota per PHPlist';break;
  case 'login': $page_title = 'Effettua l\'accesso';break;
  case 'logout': $page_title = 'Esci';break;
  case 'mclicks': $page_title = 'Statistiche dei Click sui messaggi'; break;
  case 'uclicks': $page_title = 'Statistiche dei Click sugli URL'; break;
  case 'massunconfirm': $page_title = 'Revoca di massa email confermate';break;
}
?>