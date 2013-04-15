<?php
switch ($page) {
  case 'home': $page_title = 'Główna strona Administratora';break;
  case 'setup': $page_title = 'Opcje konfiguracji';break;
  case 'about': $page_title = 'o '.NAME;break;
  case 'attributes': $page_title = 'Konfiguracja atrybutów';break;
  case 'stresstest': $page_title = 'Próba wydajności';break;
  case 'list': $page_title = 'wykaz list';break;
  case 'editattributes': $page_title = 'Konfiguracja atrybutów';break;
  case 'editlist': $page_title = 'Edycja listy';break;
  case 'checki18n': $page_title = 'Sprawdź czy istnieją tłumaczenia';break;
  case 'import4': $page_title = 'Importowanie adresów email ze zdalnej bazy danych';break;
  case 'import3': $page_title = 'Importowanie adresów email z IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Importowanie adresów email';break;
  case 'export': $page_title = 'Eksportowanie użytkowników';break;
  case 'initialise': $page_title = 'Pierwsze uruchomienie bazy danych';break;
  case 'send': $page_title = 'Wysyanie wiadomości';break;
  case 'preparesend': $page_title = 'Przygotowanie wiadomości do wysłania';break;
  case 'sendprepared': $page_title = 'Wysyłanie przygotowanej wiadomości';break;
  case 'members': $page_title = 'Lista członków';break;
  case 'users': $page_title = 'Lista wszystkich użytkowników';break;
  case 'reconcileusers': $page_title = 'Uzgadnianie użytkowników';break;
  case 'user': $page_title = 'Szczegóły użytkowników';break;
  case 'userhistory': $page_title = 'historia użytkownika';break;
  case 'messages': $page_title = 'lista wiadomości';break;
  case 'message': $page_title = 'Podgląd wiadomości';break;
  case 'processqueue': $page_title = 'Klejka wiadomości';break;
  case 'defaults': $page_title = 'Użyteczne atrybuty';break;
  case 'upgrade': $page_title = 'Aktualizacja '.NAME;break;
  case 'templates': $page_title = 'Szablony w systemie';break;
  case 'template': $page_title = 'Dodawanie i edycja szablonów';break;
  case 'viewtemplate': $page_title = 'Podgląd szablonu';break;
  case 'configure': $page_title = 'Konfiguracja '.NAME;break;
  case 'admin': $page_title = 'Edycja Administratora';break;
  case 'admins': $page_title = 'Lista administratorów';break;
  case 'adminattributes': $page_title = 'Konfiguracja atrybutów administratora';break;
  case 'processbounces': $page_title = 'Pobieranie zwrotów z serwera';break;
  case 'bounces': $page_title = 'Lista zwrotów';break;
  case 'bounce': $page_title = 'Podgląd zwrotu';break;
  case 'spageedit': $page_title = 'Edycja strony rejestrowania';break;
  case 'spage': $page_title = 'Strony rejestrowania';break;
  case 'eventlog': $page_title = 'Dziennik zdarzeń';break;
  case 'getrss': $page_title = 'Pobieranie nagłówków RSS';break;
  case 'viewrss': $page_title = 'Wyświetlanie nagłówków RSS';break;
  case 'community': $page_title = 'Witamy w społeczności PHPlist';break;
  case 'vote': $page_title = 'Głosuj na PHPlist';break;
  case 'login': $page_title = 'Zaloguj się';break;
  case 'logout': $page_title = 'Wylogowanie';break;
  case 'mclicks': $page_title = 'Statystyka kliknięć wiadomości'; break;
  case 'uclicks': $page_title = 'Statystyka kliknięć adresu URL'; break;
  case 'massunconfirm': $page_title = 'Zbiorcze anulowanie potwierdzenia wiadomości';break;
}
?>
