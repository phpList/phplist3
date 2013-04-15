<?php
switch ($page) {
  case 'home': $page_title = 'برگه آغازه مدیر';break;
  case 'setup': $page_title = 'انتخابهای پیکربندی';break;
  case 'about': $page_title = 'درباره '.NAME;break;
  case 'attributes': $page_title = 'پیکربندی ویژگیها';break;
  case 'stresstest': $page_title = 'آزمایش فشار';break;
  case 'list': $page_title = 'فهرست فهرستها';break;
  case 'editattributes': $page_title = 'پیکربندی ویژگیها';break;
  case 'editlist': $page_title = 'ویرایش یک فهرست';break;
  case 'checki18n': $page_title = 'بررسی وجود ترجمه ها';break;
  case 'import4': $page_title = 'ورود ایمیلهای از یک پایگاه داده دور';break;
  case 'import3': $page_title = 'ورود ایمیلها از IMAP';break;
  case 'import2':
  case 'import1':
  case 'import': $page_title = 'ورود ایمیلها';break;
  case 'export': $page_title = 'صدور کاربرها';break;
  case 'initialise': $page_title = 'آماده سازی پایگاه داده';break;
  case 'send': $page_title = 'فرستادن یک پیام';break;
  case 'preparesend': $page_title = 'آماده سازی پیام برای فرستادن';break;
  case 'sendprepared': $page_title = 'فرستادن پیام آماده شده';break;
  case 'members': $page_title = 'فهرست عضویت';break;
  case 'users': $page_title = 'فهرست همه کاربرها';break;
  case 'reconcileusers': $page_title = 'تطبیق کاربرها';break;
  case 'user': $page_title = 'جزئیات کاربر';break;
  case 'userhistory': $page_title = 'تاریخچه کاربر';break;
  case 'messages': $page_title = 'فهرست پیامها';break;
  case 'message': $page_title = 'دیدن پیام';break;
  case 'processqueue': $page_title = 'فرستادن صف پیام';break;
  case 'defaults': $page_title = 'چند ویژگی پیشفرض سودمند';break;
  case 'upgrade': $page_title = 'ارتقا '.NAME;break;
  case 'templates': $page_title = 'الگوهای سیستم';break;
  case 'template': $page_title = 'افزودن یا ویرایش الگو';break;
  case 'viewtemplate': $page_title = 'پیش نمایش الگو';break;
  case 'configure': $page_title = 'پیکربندی '.NAME;break;
  case 'admin': $page_title = 'ویرایش مدیر';break;
  case 'admins': $page_title = 'نمایش مدیرها';break;
  case 'adminattributes': $page_title = 'پیکربندی ویژگیهای مدیر';break;
  case 'processbounces': $page_title = 'دریافت برگشتیها از سرور';break;
  case 'bounces': $page_title = 'نمایش برگشتیها';break;
  case 'bounce': $page_title = 'دیدن برگشتی';break;
  case 'spageedit': $page_title = 'ویرایش برگه اشتراک';break;
  case 'spage': $page_title = 'برگه های اشتراک';break;
  case 'eventlog': $page_title = 'گزارش رویدادها';break;
  case 'getrss': $page_title = 'دریافت خوراکهای RSS';break;
  case 'viewrss': $page_title = 'دیدن موارد RSS';break;
  case 'community': $page_title = 'به انجمن PHPlist خوش آمدید';break;
  case 'vote': $page_title = 'به PHPlist رای دهید';break;
  case 'login': $page_title = 'ورود به سیستم';break;
  case 'logout': $page_title = 'خروج';break;
  case 'mclicks': $page_title = 'آمار کلیک پیام'; break;
  case 'uclicks': $page_title = 'آمار کلیک URL'; break;
  case 'massunconfirm': $page_title = 'لغو تایید دسته جمعی ایمیلها';break;
}
?>
