<?php
switch ($page) {
  case 'home': $page_title = 'Page Principale';break;
  case 'setup': $page_title = 'Options de Configuration';break;
  case 'about': $page_title = 'Au sujet de '.NAME;break;
  case 'attributes': $page_title = 'Configuration des Attributs';break;
  case 'stresstest': $page_title = 'Test de Stress';break;
  case 'list': $page_title = 'La Liste des Listes';break;
  case 'editattributes': $page_title = 'Configurer les Attributs';break;
  case 'editlist': $page_title = 'Modifier une liste';break;
  case 'checki18n': $page_title = 'V&eacute;rifier que les traductions existent';break;
  case 'import4': $page_title = 'Importer des emails d&rsquo;un serveur distant';break;
  case 'import3': $page_title = 'Importer des emails d&rsquo;un compte IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Importer des emails';break;
  case 'export': $page_title = 'Exporter des utilisateurs';break;
  case 'initialise': $page_title = 'Initialiser la base de donn&eacute;es';break;
  case 'send': $page_title = 'Envoyer un Message';break;
  case 'preparesend': $page_title = 'Pr&eacute;parer l&rsquo;envoi d&rsquo;un message';break;
  case 'sendprepared': $page_title = 'Envoyer un message pr&ecirc;t &agrave; l&rsquo;envoi';break;
  case 'members': $page_title = 'Les Membres d&rsquo;une liste';break;
  case 'users': $page_title = 'Montrer tous les utilisateurs';break;
  case 'reconcileusers': $page_title = 'Mettre de l&rsquo;ordre dans la base des utilisateurs';break;
  case 'user': $page_title = 'Donn&eacute;es d&rsquo;un utilisateur';break;
  case 'userhistory': $page_title = 'Historique du dossier d&rsquo;un utilisateur';break;
  case 'messages': $page_title = 'Tous les messages';break;
  case 'message': $page_title = 'Afficher un message';break;
  case 'processqueue': $page_title = 'Envoyer les messages dans la file d&rsquo;attente';break;
  case 'defaults': $page_title = 'Quelques attributs par d&eacute;faut qui sont utiles';break;
  case 'upgrade': $page_title = 'Mettre &agrave; jour '.NAME;break;
  case 'templates': $page_title = 'Mod&egrave;les inclus avec le syst&egrave;me';break;
  case 'template': $page_title = 'Ajouter ou Modifier un mod&egrave;le';break;
  case 'viewtemplate': $page_title = 'Pr&eacute;visualiser un mod&egrave;le';break;
  case 'configure': $page_title = 'Configurer '.NAME;break;
  case 'admin': $page_title = 'Modifier un Administrateur';break;
  case 'admins': $page_title = 'Liste des Administrateurs';break;
  case 'adminattributes': $page_title = 'Configurer les attributs pour les Administrateurs';break;
  case 'processbounces': $page_title = 'R&eacute;cup&eacute;rer les messages rejet&eacute;s du serveur';break;
  case 'bounces': $page_title = 'Liste des messages rejet&eacute;s';break;
  case 'bounce': $page_title = 'Afficher un message rejet&eacute;';break;
  case 'spageedit': $page_title = 'Modifier une page d&rsquo;inscription';break;
  case 'spage': $page_title = 'Pages d&rsquo;inscription';break;
  case 'eventlog': $page_title = 'Journal des &eacute;v&eacute;nements (eventlog)';break;
  case 'getrss': $page_title = 'R&eacute;cup&eacute;rer les donn&eacute;es des fils RSS';break;
  case 'viewrss': $page_title = 'Afficher les donn&eacute;es des fils RSS';break;
  case 'community': $page_title = 'Bienvenu(e) dans la communaut&eacute; PHPlist';break;
  case 'vote': $page_title = 'Votez pour PHPlist';break;
  case 'login': $page_title = 'Connexion';break;
  case 'logout': $page_title = 'D&eacute;connexion';break;
  case 'mclicks': $page_title = 'Statistiques de Clicks sur les Messages'; break;
  case 'uclicks': $page_title = 'Statistiques de Clicks sur les URL&rsquo;'; break;
}
?>
