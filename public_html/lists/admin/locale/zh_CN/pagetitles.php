<?php
switch ($page) {
  case 'home': $page_title = '管理介面首页';break;
  case 'setup': $page_title = '设定选项';break;
  case 'about': $page_title = '关于 '.NAME;break;
  case 'attributes': $page_title = '设定栏位';break;
  case 'stresstest': $page_title = '压力测试';break;
  case 'list': $page_title = '订阅人列表管理';break;
  case 'editattributes': $page_title = '编辑栏位';break;
  case 'editlist': $page_title = '编辑电子报';break;
  case 'checki18n': $page_title = '检查语言档桉';break;
  case 'import4': $page_title = '从远端资料库汇入名单';break;
  case 'import3': $page_title = '从 IMAP 伺服器汇入名单';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = '汇入名单';break;
  case 'export': $page_title = '汇出使用者';break;
  case 'initialise': $page_title = '初始化资料库';break;
  case 'send': $page_title = '发送电子报';break;
  case 'preparesend': $page_title = '准备要发送的电子报内容';break;
  case 'sendprepared': $page_title = '发送准备好的电子报内容';break;
  case 'members': $page_title = '列出会员';break;
  case 'users': $page_title = '列出所有使用者';break;
  case 'reconcileusers': $page_title = '批量更新使用者';break;
  case 'user': $page_title = '使用者细节';break;
  case 'userhistory': $page_title = '使用者记录';break;
  case 'messages': $page_title = '电子报内容列表';break;
  case 'message': $page_title = '检视电子报';break;
  case 'processqueue': $page_title = '处理队列';break;
  case 'defaults': $page_title = '部份实用的预设栏位';break;
  case 'upgrade': $page_title = '升级 '.NAME;break;
  case 'templates': $page_title = '系统中的模板';break;
  case 'template': $page_title = '新增或编辑模板';break;
  case 'viewtemplate': $page_title = '预览模板';break;
  case 'configure': $page_title = '设定 '.NAME;break;
  case 'admin': $page_title = '编辑管理者';break;
  case 'admins': $page_title = '列出管理者';break;
  case 'adminattributes': $page_title = '设定管理者栏位';break;
  case 'processbounces': $page_title = '从伺服器取回退信';break;
  case 'bounces': $page_title = '退信列表';break;
  case 'bounce': $page_title = '检视退信';break;
  case 'spageedit': $page_title = '编辑订阅页面';break;
  case 'spage': $page_title = '订阅页面';break;
  case 'eventlog': $page_title = '事件记录';break;
  case 'getrss': $page_title = '取回 RSS 内容';break;
  case 'viewrss': $page_title = '检视 RSS 资料';break;
  case 'community': $page_title = '欢迎来到 PHPlist 社群';break;
  case 'vote': $page_title = '为 PHPlist 评分';break;
  case 'login': $page_title = '登入';break;
  case 'logout': $page_title = '退出';break;
  case 'mclicks': $page_title = '信件点阅统计'; break;
  case 'uclicks': $page_title = '网址点阅统计'; break;
  case 'massunconfirm': $page_title = '大量重新确认信箱';break;
}
?>