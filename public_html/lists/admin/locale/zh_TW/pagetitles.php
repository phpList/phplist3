<?php
switch ($page) {
  case 'home': $page_title = '管理介面首頁';break;
  case 'setup': $page_title = '設定選項';break;
  case 'about': $page_title = '關於 '.NAME;break;
  case 'attributes': $page_title = '設定欄位';break;
  case 'stresstest': $page_title = '壓力測試';break;
  case 'list': $page_title = '電子報主題';break;
  case 'editattributes': $page_title = '編輯欄位';break;
  case 'editlist': $page_title = '編輯電子報';break;
  case 'checki18n': $page_title = '檢查語言檔案';break;
  case 'import4': $page_title = '從遠端資料庫匯入名單';break;
  case 'import3': $page_title = '從 IMAP 伺服器匯入名單';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = '匯入名單';break;
  case 'export': $page_title = '匯出使用者';break;
  case 'initialise': $page_title = '初始化資料庫';break;
  case 'send': $page_title = '寄送電子報';break;
  case 'preparesend': $page_title = '準備要寄送的電子報內容';break;
  case 'sendprepared': $page_title = '寄送準備好的電子報內容';break;
  case 'members': $page_title = '列出會員';break;
  case 'users': $page_title = '列出所有使用者';break;
  case 'reconcileusers': $page_title = '大量更新使用者';break;
  case 'user': $page_title = '使用者細節';break;
  case 'userhistory': $page_title = '使用者紀錄';break;
  case 'messages': $page_title = '電子報內容列表';break;
  case 'message': $page_title = '檢視電子報';break;
  case 'processqueue': $page_title = '處理佇列';break;
  case 'defaults': $page_title = '部份實用的預設欄位';break;
  case 'upgrade': $page_title = '升級 '.NAME;break;
  case 'templates': $page_title = '系統中的樣板';break;
  case 'template': $page_title = '新增或編輯樣板';break;
  case 'viewtemplate': $page_title = '預覽樣板';break;
  case 'configure': $page_title = '設定 '.NAME;break;
  case 'admin': $page_title = '編輯管理者';break;
  case 'admins': $page_title = '列出管理者';break;
  case 'adminattributes': $page_title = '設定管理者欄位';break;
  case 'processbounces': $page_title = '從伺服器取回退信';break;
  case 'bounces': $page_title = '退信列表';break;
  case 'bounce': $page_title = '檢視退信';break;
  case 'spageedit': $page_title = '編輯訂閱頁面';break;
  case 'spage': $page_title = '訂閱頁面';break;
  case 'eventlog': $page_title = '事件紀錄';break;
  case 'getrss': $page_title = '取回 RSS 內容';break;
  case 'viewrss': $page_title = '檢視 RSS 資料';break;
  case 'community': $page_title = '歡迎來到 PHPlist 社群';break;
  case 'vote': $page_title = '為 PHPlist 評分';break;
  case 'login': $page_title = '登入';break;
  case 'logout': $page_title = '登出';break;
  case 'mclicks': $page_title = '信件點閱統計'; break;
  case 'uclicks': $page_title = '網址點閱統計'; break;
  case 'massunconfirm': $page_title = '大量重新確認信箱';break;
}
?>
