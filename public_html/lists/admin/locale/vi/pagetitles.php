<?php
switch ($page) {
  case 'home': $page_title = 'Trang qu&#7843;n tr&#7883;';break;
  case 'setup': $page_title = 'L&#7921;a ch&#7885;n cho c&#7845;u h&igrave;nh';break;
  case 'about': $page_title = 'th&ocirc;ng tin v&#7873; '.NAME;break;
  case 'attributes': $page_title = 'Thi&#7871;t l&#7853;p thu&#7897;c t&iacute;nh';break;
  case 'stresstest': $page_title = 'Stress Test';break;
  case 'list': $page_title = 'c&aacute;c danh s&aacute;ch';break;
  case 'editattributes': $page_title = 'thi&#7871;t l&#7853;p thu&#7897;c t&iacute;nh';break;
  case 'editlist': $page_title = 's&#7917;a &#273;&#7893;i danh s&aacute;ch';break;
  case 'checki18n': $page_title = 'ki&#7875;m tra bi&ecirc;n d&#7883;ch';break;
  case 'import4': $page_title = 'nh&#7853;p email t&#7915; CSDL kh&aacute;ch';break;
  case 'import3': $page_title = 'nh&#7853;p email t&#7915; m&aacute;y ch&#7911; h&#7895; tr&#7907; giao th&#7913;c IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'nh&#7853;p v&agrave;o email ';break;
  case 'export': $page_title = 'Xu&#7845;t ra  users';break;
  case 'initialise': $page_title = 'kh&#7903;i t&#7841;o CSDL ';break;
  case 'send': $page_title = 'so&#7841;n th&#432;';break;
  case 'preparesend': $page_title = 'so&#7841;n tr&#432;&#7899;c th&#432; &#273;&#7875; g&#7917;i ra ';break;
  case 'sendprepared': $page_title = 'g&#7917;i th&#432; &#273;&atilde; so&#7841;n tr&#432;&#7899;c';break;
  case 'members': $page_title = 'List Membership';break;
  case 'users': $page_title = 't&#7845;t c&#7843; h&#7897;i vi&ecirc;n';break;
  case 'reconcileusers': $page_title = 'Reconcile users';break;
  case 'user': $page_title = 'chi ti&#7871;t h&#7897;i vi&ecirc;n';break;
  case 'userhistory': $page_title = 'ti&#7875;u s&#7917; h&#7897;i vi&ecirc;n';break;
  case 'messages': $page_title = 'danh s&aacute;ch th&#432;';break;
  case 'message': $page_title = 'xem th&#432;';break;
  case 'processqueue': $page_title = 'g&#7917;i th&#432; trong h&agrave;ng &#273;&#7907;i';break;
  case 'defaults': $page_title = 'M&#7897;t s&#7889; thu&#7897;c t&iacute;nh m&#7863;c &#273;&#7883;nh h&#7919;u &iacute;ch';break;
  case 'upgrade': $page_title = 'n&acirc;ng c&#7845;p '.NAME;break;
  case 'templates': $page_title = 'th&#432; m&#7851;u trong h&#7879; th&#7889;ng';break;
  case 'template': $page_title = 'Th&ecirc;m ho&#7863;c so&#7841;n th&#432; m&#7851;u';break;
  case 'viewtemplate': $page_title = 'Xem tr&#432;&#7899;c th&#432; m&#7851;u';break;
  case 'configure': $page_title = 'Thi&#7871;t l&#7853;p '.NAME;break;
  case 'admin': $page_title = 's&#7917;a &#273;&#7893;i qu&#7843;n tr&#7883; vi&ecirc;n';break;
  case 'admins': $page_title = 'danh s&aacute;ch qu&#7843;n tr&#7883; vi&ecirc;n';break;
  case 'adminattributes': $page_title = 'thi&#7871;t l&#7853;p thu&#7897;c t&iacute;nh cho qu&#7843;n tr&#7883; vi&ecirc;n';break;
  case 'processbounces': $page_title = 'L&#7845;y th&#432; tr&#7843; l&#7841;i t&#7915; m&aacute;y ch&#7911;';break;
  case 'bounces': $page_title = 'danh s&aacute;ch th&#432; tr&#7843; l&#7841;i';break;
  case 'bounce': $page_title = 'xem th&#432; tr&#7843; l&#7841;i';break;
  case 'spageedit': $page_title = 's&#7917;a &#273;&#7893;i trang &#273;&#259;ng k&yacute;';break;
  case 'spage': $page_title = 'trang &#273;&#259;ng k&yacute;';break;
  case 'eventlog': $page_title = 'ghi ch&eacute;p c&aacute;c s&#7921; ki&#7879;n';break;
  case 'getrss': $page_title = 'l&#7845;y tin qua  RSS feeds';break;
  case 'viewrss': $page_title = 'xem tin c&aacute;c m&#7909;c tin RSS';break;
  case 'community': $page_title = 'Ch&agrave;o m&#7915;ng b&#7841;n t&#7899;i c&#7897;ng &#273;&#7897;ng PHPList ';break;
  case 'vote': $page_title = 'B&#7887; phi&#7871;u cho PHPList ';break;
  case 'login': $page_title = '&#273;&#259;ng nh&#7853;p';break;
  case 'logout': $page_title = 'tho&aacute;t';break;
  case 'mclicks': $page_title = 'Message Click Statistics'; break;
  case 'uclicks': $page_title = 'URL Click Statistics'; break;
  case 'massunconfirm': $page_title = 'b&#7887; x&aacute;c nh&#7853;n email v&#7899;i s&#7889; l&#432;&#7907;ng l&#7899;n';break;
}
?>
