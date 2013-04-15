<?php
switch ($page) {
  case 'home': $page_title = 'メイン管理者ページ';break;
  case 'setup': $page_title = 'コンフィギュレーション オプション';break;
  case 'about': $page_title = 'About '.NAME;break;
  case 'attributes': $page_title = '属性の設定';break;
  case 'stresstest': $page_title = '負荷テスト';break;
  case 'list': $page_title = 'メールマガジンリスト';break;
  case 'editattributes': $page_title = '属性の設定';break;
  case 'editlist': $page_title = 'リストの編集';break;
  case 'checki18n': $page_title = '翻訳があるかチェック';break;
  case 'import4': $page_title = 'リモートデータベースから電子メールをインポート';break;
  case 'import3': $page_title = 'IMAPから電子メールをインポート';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = '電子メールのインポート';break;
  case 'export': $page_title = 'ユーザのエクスポート';break;
  case 'initialise': $page_title = 'データベースの初期化';break;
  case 'send': $page_title = 'メッセージ送信';break;
  case 'preparesend': $page_title = '送信メッセージの準備';break;
  case 'sendprepared': $page_title = '準備済メッセージを送信';break;
  case 'members': $page_title = 'リストメンバーシップ';break;
  case 'users': $page_title = 'すべてのユーザのリスト';break;
  case 'reconcileusers': $page_title = 'ユーザの調整';break;
  case 'user': $page_title = 'ユーザの詳細';break;
  case 'userhistory': $page_title = 'ユーザの履歴';break;
  case 'messages': $page_title = 'メッセージのリスト';break;
  case 'message': $page_title = 'メッセージ閲覧';break;
  case 'processqueue': $page_title = 'メッセージキュー送信';break;
  case 'defaults': $page_title = 'いくつかの役立つデフォルト属性';break;
  case 'upgrade': $page_title = 'アップグレード '.NAME;break;
  case 'templates': $page_title = 'システムでのテンプレート';break;
  case 'template': $page_title = 'テンプレートを追加または編集';break;
  case 'viewtemplate': $page_title = 'テンプレートプレビュー';break;
  case 'configure': $page_title = '設定 '.NAME;break;
  case 'admin': $page_title = '管理者の編集';break;
  case 'admins': $page_title = '管理者リスト';break;
  case 'adminattributes': $page_title = '管理者属性の設定';break;
  case 'processbounces': $page_title = 'サーバからのバウンスメールの回収';break;
  case 'bounces': $page_title = 'バウンスリスト';break;
  case 'bounce': $page_title = 'バウンスを閲覧';break;
  case 'spageedit': $page_title = '購読ページの編集';break;
  case 'spage': $page_title = '購読ページ';break;
  case 'eventlog': $page_title = 'イベントのログ';break;
  case 'getrss': $page_title = 'RSSフィードの回収';break;
  case 'viewrss': $page_title = 'RSS Itemsの閲覧';break;
  case 'community': $page_title = 'PHPlistコミュニティーへようこそ';break;
  case 'vote': $page_title = 'PHPlistに対し投票';break;
  case 'login': $page_title = 'ログイン';break;
  case 'logout': $page_title = 'ログアウト';break;
  case 'mclicks': $page_title = 'メッセージクリック統計'; break;
  case 'uclicks': $page_title = 'URLクリック統計'; break;
  case 'massunconfirm': $page_title = '電子メールの大量Unconfirm処理';break;
}
?>
