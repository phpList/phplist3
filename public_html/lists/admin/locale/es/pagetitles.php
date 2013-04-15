<?php
switch ($page) {
  case 'home': $page_title = 'P&aacute;gina principal de administraci&oacute;n';break;
  case 'setup': $page_title = 'Opciones de configuraci&oacute;n';break;
  case 'about': $page_title = 'Acerca de '.NAME;break;
  case 'attributes': $page_title = 'Configurar atributos';break;
  case 'stresstest': $page_title = 'Prueba de stress';break;
  case 'list': $page_title = 'La lista de las listas';break;
  case 'editattributes': $page_title = 'Configurar atributos';break;
  case 'editlist': $page_title = 'Editar una lista';break;
  case 'checki18n': $page_title = 'Comprobar que existen traducciones';break;
  case 'import4': $page_title = 'Importar emails de una base de datos remota';break;
  case 'import3': $page_title = 'Importar emails de IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'Importar emails';break;
  case 'export': $page_title = 'Exportar usuarios';break;
  case 'initialise': $page_title = 'Initializar la base de datos';break;
  case 'send': $page_title = 'Enviar un mensaje';break;
  case 'preparesend': $page_title = 'Preparar un mensaje para enviar';break;
  case 'sendprepared': $page_title = 'Enviar un mensaje preparado';break;
  case 'members': $page_title = 'Miembros de la lista';break;
  case 'users': $page_title = 'Lista de todos los usuarios';break;
  case 'reconcileusers': $page_title = 'Conciliar usuarios';break;
  case 'user': $page_title = 'Detalles de un usuario';break;
  case 'userhistory': $page_title = 'Historial de un usuario';break;
  case 'messages': $page_title = 'Todos los mensajes';break;
  case 'message': $page_title = 'Ver un mensaje';break;
  case 'processqueue': $page_title = 'Enviar los mensajes que
  est&aacute;n en cola';break;
  case 'defaults': $page_title = 'Algunos atributos por defecto &uacute;tiles';break;
  case 'upgrade': $page_title = 'Actualizar '.NAME;break;
  case 'templates': $page_title = 'Plantillas que hay en el sistema';break;
  case 'template': $page_title = 'A&ntilde;adir o editar una plantilla';break;
  case 'viewtemplate': $page_title = 'Vista previa de la plantilla';break;
  case 'configure': $page_title = 'Configurar '.NAME;break;
  case 'admin': $page_title = 'Editar un administrador';break;
  case 'admins': $page_title = 'Enumerar administradores';break;
  case 'adminattributes': $page_title = 'Configurar los atributos de administrador';break;
  case 'processbounces': $page_title = 'Recuperar del servidor los
  correos rebotados';break;
  case 'bounces': $page_title = 'Enumerar correos rebotados';break;
  case 'bounce': $page_title = 'Ver un correo rebotado';break;
  case 'spageedit': $page_title = 'Editar una p&aacute;gina de inscripci&oacute;n';break;
  case 'spage': $page_title = 'P&aacute;ginas de inscripci&oacute;n';break;
  case 'eventlog': $page_title = 'Registro de eventos';break;
  case 'getrss': $page_title = 'Obtener canales RSS';break;
  case 'viewrss': $page_title = 'Ver elementos RSS';break;
  case 'community': $page_title = 'Bienvenido a la comunidad phplist';break;
  case 'vote': $page_title = 'Vote por phplist';break;
  case 'login': $page_title = 'Conectarse';break;
  case 'logout': $page_title = 'Desconectarse';break;
  case 'mclicks': $page_title = 'Estad&iacute;sticas de clicks de un mensaje'; break;
  case 'uclicks': $page_title = 'Estad&iacute;sticas de clicks de una URL'; break;
}
?>
