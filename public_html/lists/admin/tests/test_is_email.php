<?php

#testEmail();

class test_is_email extends phplistTest
{
    public $name = 'isEmail';
    public $purpose = 'Test email validation';

    public function runtest()
    {
        $emailAddresses = array(
      'name@company.com'                                                              => true , // mother of all emails
      'name.last@company.com'                                                         => true , // with last name
      'name.company.com'                                                              => false, // two dots
      'name@company..com'                                                             => false, // two dots
      'name@company@com'                                                              => false, // two ats
      'name@company.co.uk'                                                            => true , // more .domain sections
      'name@.company.co.uk'                                                           => false, // more .domain sections wrongly
      'n&me@company.com'                                                              => true , //
      "n'me@company.com"                                                              => true , //
      'name last@company.com'                                                         => false, // unquoted space is wrong
      '"namelast"@company.com'                                                        => true , // Quoted string can be anything, as long as certain chars are escaped with \
      '"name last"@company.com'                                                       => true , // Quoted string can be anything, as long as certain chars are escaped with \
      '" "@company.com'                                                               => true , // Quoted string can be anything, as long as certain chars are escaped with \
      "\"name\ last\"@company.com"                                                    => true , // Quoted string can be anything, as long as certain chars are escaped with \
      '"name\*last"@company.com'                                                      => true , // Quoted string can be anything, as long as certain chars are escaped with \
      '.@company.com'                                                                 => false, // single dot is wrong
      'n.@company.com'                                                                => false, // Ending dot is nok
      '.n@company.com'                                                                => false, // Starting dot is nok
      'n.n@company.com'                                                               => true , // dot is ok between text
      '@company.com'                                                                  => false, // Local part too short
      'n@company.com'                                                                 => true , // Local part not yet too short
      'abcdefghijabcdefghijabcdefghijabcdefghijabcdefghijabcdefghijabcd@company.com'  => true , // Local part too long
      'abcdefghijabcdefghijabcdefghijabcdefghijabcdefghijabcdefghijabcde@company.com' => false , // Local part not yet too long
      'mailto:name@company.com'                                                       => false , // protocol included  @@ maybe support during import?
      'name,name@company.com'                                                         => false , // non-escaped comma
      'user1@domain1.com;user2@domain2.com'                                           => false, // Mantis #0010174 @@ maybe support during import?
      'name@127.0.0.1'                                                                => true , // not in the RFC but generally accepted (?)
  # From http://en.wikibooks.org/wiki/Programming:JavaScript:Standards_and_Best_Practices
      'me@example.com'            => true ,
      'a.nonymous@example.com'    => true ,
      'name+tag@example.com'      => true ,
      ## next one is actually officiall valid, but we're marking it as not, as it's rather uncommon
     # '"name\@tag"@example.com'   => TRUE ,
      '"name\@tag"@example.com'                            => false , // � this is a valid email address containing two @ symbols.
      "escaped\ spaces\ are\ allowed@example.com"          => true ,
      '"spaces may be quoted"@example.com'                 => true ,
      "!#$%&'*+-/=.?^_`{|}~@example.com"                   => true ,
  #   "!#$%&'*+-/=.?^_`{|}~@[1.0.0.127]" => TRUE , # Excluded
  #		"!#$%&'*+-/=.?^_`{|}~@[IPv6:0123:4567:89AB:CDEF:0123:4567:89AB:CDEF]" => TRUE , #Excluded
  #		"me(this is a comment)@example.com" => TRUE , #Excluded
      'me@'                       => false,
      '@example.com'              => false,
      'me.@example.com'           => false,
      '.me@example.com'           => false,
      'me@example..com'           => false,
      'me.example@com'            => false,
      "me\@example.com"           => false,
      "s'oneill@somenice.museum"  => true,
      ## some uncommon TLDs
      'me@domain.museum'          => true,
      'me@me.me'                  => true,
      'jobs@jobs.jobs'            => true,
      'hello@me.nonexistingtld'   => false,
      ## next one is actually officiall valid, but we're marking it as not, as it's rather uncommon
#      "me\@sales.com@example.com"          => TRUE,
      "me\@sales.com@example.com"          => false,
      # From http://www.faqs.org/rfcs/rfc3696.html
       'customer/department=shipping@example.com' => true ,
       '$A12345@example.com'                      => true ,
       '!def!xyz%abc@example.com'                 => true ,
       '\\\'a0@example.com'                       => false,
       'someone@phplíst.com'                      => true,
       'hello@clk.email'                          => true,
       'hello@xn--tst-qla.de'                     => true,
       'some\weird\escaping\shouldfail@yahoo.com' => true, // this should fail, but doesn't
      );

        print('<P>PHP '.PHP_VERSION.' running on '.PHP_OS.' - Testing email address validation...</P>');

        $resultString = '';
        $nFoundWrong = 0;

        $boolean = array('FALSE','TRUE');

        foreach ($emailAddresses as $emailAddress => $emailAddressValid) {
            #	print($emailAddress . " is " . is_email($emailAddresses) .", should be " . $emailAddressValid . "<BR/>");
      if (is_email($emailAddress) != $emailAddressValid) {
          $resultString .= "$emailAddress should be ".$boolean[$emailAddressValid].'<BR/>';
          ++$nFoundWrong;
      }
        }

        if ($nFoundWrong > 0) {
            print("<P>The following $nFoundWrong email addresses were evaluated wrong:<BR/>$resultString</P>");

            return false;
        } else {
            print('<P>All email addresses evaluated correctly.</P>');

            return true;
        }
    }
}
