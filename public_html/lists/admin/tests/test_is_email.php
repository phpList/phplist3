<?php

#require_once dirname(__FILE__)."./../commonlib/lib/userlib.php";

#testEmail();

class test_is_email extends phplistTest {
  public $name = 'isEmail';
  public $purpose = 'Test email validation';
  

  function runtest(){
    $emailAddresses = array(
      "name@company.com"      	=> TRUE , // mother of all emails
      "name.last@company.com" 	=> TRUE , // with last name
      "name.company.com" 				=> FALSE, // two dots
      "name@company..com"  			=> FALSE, // two dots
      "name@company@com" 				=> FALSE, // two ats
      "name@company.co.uk" 		  => TRUE , // more .domain sections
      "name@.company.co.uk" 	  => FALSE, // more .domain sections wrongly
      "n&me@company.com"	 	    => TRUE , // 
      "n'me@company.com"	 	  	=> TRUE , // 
      "name last@company.com"	 	=> FALSE, // unquoted space is wrong
      '"namelast"@company.com'  => TRUE , // Quoted string can be anything, as long as certain chars are escaped with \
      '"name last"@company.com' => TRUE , // Quoted string can be anything, as long as certain chars are escaped with \
      '" "@company.com' 				=> TRUE , // Quoted string can be anything, as long as certain chars are escaped with \
      "\"name\ last\"@company.com" => TRUE , // Quoted string can be anything, as long as certain chars are escaped with \
      '"name\*last"@company.com'=> TRUE , // Quoted string can be anything, as long as certain chars are escaped with \
      ".@company.com"          	=> FALSE, // single dot is wrong
      "n.@company.com"          => FALSE, // Ending dot is nok
      ".n@company.com"          => FALSE, // Starting dot is nok
      "n.n@company.com"         => TRUE , // dot is ok between text
      "@company.com"          	=> FALSE, // Local part too short
      "n@company.com"          	=> TRUE , // Local part not yet too short
      "abcdefghijabcdefghijabcdefghijabcdefghijabcdefghijabcdefghijabcd@company.com"  => TRUE , // Local part too long
      "abcdefghijabcdefghijabcdefghijabcdefghijabcdefghijabcdefghijabcde@company.com" => FALSE , // Local part not yet too long
      "mailto:name@company.com" => FALSE , // protocol included  @@ maybe support during import?
      "name,name@company.com"   => FALSE , // non-escaped comma
      "user1@domain1.com;user2@domain2.com"       => FALSE, // Mantis #0010174 @@ maybe support during import?
      "name@127.0.0.1"          => TRUE , // not in the RFC but generally accepted (?)
  # From http://en.wikibooks.org/wiki/Programming:JavaScript:Standards_and_Best_Practices
      "me@example.com"            => TRUE ,
      "a.nonymous@example.com"    => TRUE ,
      "name+tag@example.com"      => TRUE ,
      ## next one is actually officiall valid, but we're marking it as not, as it's rather uncommon
     # '"name\@tag"@example.com'   => TRUE , 
      '"name\@tag"@example.com'   => FALSE , // Ð this is a valid email address containing two @ symbols.
      "escaped\ spaces\ are\ allowed@example.com"          => TRUE ,
      '"spaces may be quoted"@example.com'        => TRUE ,
      "!#$%&'*+-/=.?^_`{|}~@example.com"          => TRUE ,
  #   "!#$%&'*+-/=.?^_`{|}~@[1.0.0.127]" => TRUE , # Excluded
  #		"!#$%&'*+-/=.?^_`{|}~@[IPv6:0123:4567:89AB:CDEF:0123:4567:89AB:CDEF]" => TRUE , #Excluded
  #		"me(this is a comment)@example.com" => TRUE , #Excluded
      "me@"                       => FALSE,
      "@example.com"              => FALSE,
      "me.@example.com"           => FALSE,
      ".me@example.com"           => FALSE,
      "me@example..com"           => FALSE,
      "me.example@com"            => FALSE,
      "me\@example.com"           => FALSE,
      ## some uncommon TLDs
      "me@domain.museum"          => TRUE,
      "me@me.me"                  => TRUE,
      "jobs@jobs.jobs"            => TRUE,
      "hello@me.nonexistingtld"   => FALSE,
      ## next one is actually officiall valid, but we're marking it as not, as it's rather uncommon
#      "me\@sales.com@example.com"          => TRUE,
      "me\@sales.com@example.com"          => FALSE,
      # From http://www.faqs.org/rfcs/rfc3696.html
       "customer/department=shipping@example.com" => TRUE ,
       '$A12345@example.com'      => TRUE ,
       "!def!xyz%abc@example.com" => TRUE ,
    );
     
    print("<P>PHP " . PHP_VERSION . " running on " . PHP_OS . " - Testing email address validation...</P>");
    
    $resultString ="";
    $nFoundWrong = 0;
    
    $boolean = array("FALSE","TRUE");
    
    foreach ($emailAddresses as $emailAddress => $emailAddressValid)
    {
    #	print($emailAddress . " is " . is_email($emailAddresses) .", should be " . $emailAddressValid . "<BR/>");
      if (is_email($emailAddress) != $emailAddressValid ) {
        $resultString .= "$emailAddress should be " . $boolean[$emailAddressValid] . "<BR/>";
        $nFoundWrong++;  
      }
    }
    
    if ($nFoundWrong > 0) {
      print("<P>The following $nFoundWrong email addresses were evaluated wrong:<BR/>$resultString</P>");
      return false;
    } else {
      print("<P>All email addresses evaluated correctly.</P>");
      return true;
    }
  }
}

?>
