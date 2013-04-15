<?php
switch ($page) {
  case 'home': $page_title = 'Vpis za Administratorje';break;
  case 'setup': $page_title = 'Konfiguracija';break;
  case 'about': $page_title = 'O PHPlisti '.NAME;break;
  case 'attributes': $page_title = 'Uredi atribute';break;
  case 'stresstest': $page_title = 'Stres Test';break;
  case 'list': $page_title = 'Seznam kategorij';break;
  case 'editattributes': $page_title = 'Urejanje atributov';break;
  case 'editlist': $page_title = 'Urejanje kategorij';break;
  case 'checki18n': $page_title = 'Preveri, da prevod obstaja';break;
  case 'import4': $page_title = 'Uvozi e-naslove iz oddaljene baze';break;
  case 'import3': $page_title = 'Uvozi e-naslove iz IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Uvozi e-naslove';break;
  case 'export': $page_title = 'Izvozi uporabnike';break;
  case 'initialise': $page_title = 'Inicializiraj podatkovno bazo';break;
  case 'send': $page_title = 'Pošlji sporočilo';break;
  case 'preparesend': $page_title = 'Pripravi sporočilo za pošiljanje';break;
  case 'sendprepared': $page_title = 'Pošlji pripravljeno sporočilo';break;
  case 'members': $page_title = 'Seznam članstev';break;
  case 'users': $page_title = 'Seznam vseh uporabnikov';break;
  case 'reconcileusers': $page_title = 'Uredi uporabnike';break;
  case 'user': $page_title = 'Podrobnosti uporabnika';break;
  case 'userhistory': $page_title = 'Zgodovina uporabnika';break;
  case 'messages': $page_title = 'Seznam sporočil';break;
  case 'message': $page_title = 'Poglej sporočilo';break;
  case 'processqueue': $page_title = 'Pošlji sporočilo';break;
  case 'defaults': $page_title = 'Nekaj uporabnih privzetih atributov';break;
  case 'upgrade': $page_title = 'Nadgradnja '.NAME;break;
  case 'templates': $page_title = 'Predloge v sistemu';break;
  case 'template': $page_title = 'Dodaj ali uredi Predlogo';break;
  case 'viewtemplate': $page_title = 'Predogled predloge';break;
  case 'configure': $page_title = 'Urejanje '.NAME;break;
  case 'admin': $page_title = 'Urejanje administratorja';break;
  case 'admins': $page_title = 'Seznam administratorjev';break;
  case 'adminattributes': $page_title = 'Urejanje atributov administratorjev';break;
  case 'processbounces': $page_title = 'Pridobivanje odbojev s strežnika';break;
  case 'bounces': $page_title = 'Seznam odbojev';break;
  case 'bounce': $page_title = 'Ogled odboja';break;
  case 'spageedit': $page_title = 'Urejanje naročniške strani';break;
  case 'spage': $page_title = 'Naročniške strani';break;
  case 'eventlog': $page_title = 'Dnevnik dogodkov';break;
  case 'getrss': $page_title = 'Pridobi RSS vire';break;
  case 'viewrss': $page_title = 'Pregled RSS virov';break;
  case 'community': $page_title = 'dobrodošli v PHPlist skupnosti';break;
  case 'vote': $page_title = 'Glasuj za PHPlist';break;
  case 'login': $page_title = 'Vpis';break;
  case 'logout': $page_title = 'Izpis';break;
  case 'mclicks': $page_title = 'Statistika klikov za sporočilo'; break;
  case 'uclicks': $page_title = 'Statistika klikov za URL'; break;
  case 'massunconfirm': $page_title = 'Masovna določitev e-pošt za nepotrjene';break;
}
?>
