<?php
switch ($page) {
  case 'home': $page_title = 'P&aacute;gina Principal do Admin';break;
  case 'setup': $page_title = 'Op&ccedil;&otilde;es de Configura&ccedil;&atilde;o';break;
  case 'about': $page_title = 'Sobre '.NAME;break;
  case 'attributes': $page_title = 'Configurar Atributos';break;
  case 'stresstest': $page_title = 'Teste de Estresse';break;
  case 'list': $page_title = 'A Lista das Listas';break;
  case 'editattributes': $page_title = 'Configurar Atributos';break;
  case 'editlist': $page_title = 'Editar uma lista';break;
  case 'checki18n': $page_title = 'Verificar se essas tradu&ccedil;&otilde;es existem';break;
  case 'import4': $page_title = 'Importar emails de uma base de dados remota';break;
  case 'import3': $page_title = 'Importar emails do IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Importar emails';break;
  case 'export': $page_title = 'Exportar usu&aacute;rios';break;
  case 'initialise': $page_title = 'Iniciar o banco de dados';break;
  case 'send': $page_title = 'Enviar uma mensagem';break;
  case 'preparesend': $page_title = 'Prepara uma mensagem para ser enviada';break;
  case 'sendprepared': $page_title = 'Enviar uma mensagem compartilhada';break;
  case 'members': $page_title = 'Lista de Membros';break;
  case 'users': $page_title = 'Lista de Todos os usu&aacute;rios';break;
  case 'reconcileusers': $page_title = 'Corrigir Usu&aacute;rios';break;
  case 'user': $page_title = 'Detalhes de um usu&aacute;rio';break;
  case 'userhistory': $page_title = 'Hist&oacute;rico de um usu&aacute;rio';break;
  case 'messages': $page_title = 'Todas as mensagens';break;
  case 'message': $page_title = 'Exibir uma mensagem';break;
  case 'processqueue': $page_title = 'Enviar uma mensagem na fila';break;
  case 'defaults': $page_title = 'Alguns atributos padr&atilde;o &uacute;teis';break;
  case 'upgrade': $page_title = 'Atualizar'.NAME;break;
  case 'templates': $page_title = 'Modelos no sistema';break;
  case 'template': $page_title = 'Adicionar ou Editar um modelo';break;
  case 'viewtemplate': $page_title = 'Visualizar Modelo';break;
  case 'configure': $page_title = 'Configurar '.NAME;break;
  case 'admin': $page_title = 'Editar um Administrador';break;
  case 'admins': $page_title = 'Listar Administradores';break;
  case 'adminattributes': $page_title = 'Configurar atributos do Adminstrador';break;
  case 'processbounces': $page_title = 'Buscar emails de erro a partir do servidor';break;
  case 'bounces': $page_title = 'Listar emails de erro';break;
  case 'bounce': $page_title = 'Exibir um email de erro';break;
  case 'spageedit': $page_title = 'Editar uma p&aacute;gina de inscri&ccedil;&atilde;o';break;
  case 'spage': $page_title = 'p&aacute;ginas de Inscri&ccedil;&atilde;o';break;
  case 'eventlog': $page_title = 'Relat&oacute;rio de eventos';break;
  case 'getrss': $page_title = 'Buscar por arquivos RSS';break;
  case 'viewrss': $page_title = 'Ver RSS &Iacutetens';break;
  case 'community': $page_title = 'Bem-vindo &agrave; Comunidade PHPlist';break;
  case 'vote': $page_title = 'Vote para no PHPlist';break;
  case 'login': $page_title = 'Conectar';break;
  case 'logout': $page_title = 'Desconectar';break;
  case 'mclicks': $page_title = 'Estat&iacute;sticas de Clique em Mensagens'; break;
  case 'uclicks': $page_title = 'Estat&iacute;sticas de Clique em URL'; break;
}
?>
