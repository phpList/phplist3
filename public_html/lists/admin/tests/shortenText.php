<?php

class shortenText extends phplistTest
{
    public $name = 'shortenText';
    public $purpose = 'verify shortenText';

    private $tests = array(

        'short' => array(
            'orig'   => 'short text',
            'result' => 'short text',
        ),
        'long' => array(
            'orig'   => 'some longer text, that should be abbreviated',
            'result' => 'some longer text, th ... bbreviated',
        ),
        'longer' => array(
            'orig'   => 'Lorem ipsum dolor sit amet, per ut lorem animal iisque, quas libris duo no. Ut iisque epicuri sed, has case ubique persecuti id. Vis liber accusam imperdiet ex, no has mazim probatus scripserit, officiis praesent sadipscing eam in. Ne aperiam fabulas pri, an offendit rationibus reprimique his. No duo pertinax electram, nam te sumo sonet everti. Tritani ancillae eum ne, duo cu officiis constituam.
Ad erant soluta adipiscing duo, labore semper senserit eum no. At suas virtute has, ad mel ipsum placerat torquatos. Duo at nulla saperet, iisque albucius antiopam nec ut. Te decore graece possit mea, ut justo choro saepe his, in est aliquip scripserit.
Nam soluta contentiones no. Adolescens posidonium interesset ei per, per numquam petentium cu. Eius epicuri adipisci qui in. No adhuc saperet oportere cum. No vide erat mnesarchum his, omnium omittantur nam te, atomorum convenire no vis.
Munere feugait nec ne, ex vim similique expetendis appellantur, an has quaeque appareat. Feugiat pericula abhorreant ex sed, quot wisi malorum quo at. Et nam augue patrioque scriptorem. Per accusam principes cu. Virtute accusam conceptam ius ei, vim et minim dicant.
Accusam persecuti ea vel, nam id minim diceret minimum. Ut per nostrum perpetua. In corrumpit similique vim, in falli suavitate persequeris vel. Aeterno efficiendi te qui, vix dicta torquatos eu. Vix an pericula facilisis dissentias, quo in fabulas accommodare. His no cibo adhuc dolorum, ad veniam omnium delicatissimi vix, ut qui nonumy oporteat senserit.
Vim melius reprehendunt ea. Ad sit veri timeam, eius doctus eos ad. Modo alienum suscipiantur ea qui. Ex brute definiebas his, et invenire corrumpit quaerendum vel. Audiam invidunt duo ei, tibique apeirian indoctum est no.
Tollit pertinax est eu, dictas eleifend mea id. Dicant debitis sadipscing id vis, ornatus deserunt cu eam. Vix illud mollis consectetuer cu. Solum recteque quo an, has ne molestiae adipiscing. Mei ex quas suscipit.
Ius an alienum assentior mnesarchum, ea minim semper temporibus usu, copiosae conceptam liberavisse te nec. Etiam mazim harum ne est, virtute facilisi scripserit an eam. Mel tale vocent vidisse ne, cu porro omnium mei. Ne sit affert malorum referrentur, ius et impetus aeterno urbanitas. Ut soleat altera appetere vix.
Per ea congue tation scriptorem. Scripta probatus instructior ad pri, in nusquam consequuntur qui. Ius an nostrud splendide, ius cu mucius propriae, novum mollis legimus quo eu. Mutat rebum soluta ne quo. Facete nostrud aliquid mei id, at sea nihil detraxit, qui eu veri velit appellantur. Et sea elitr repudiandae.
Nam quod copiosae ea, cu per quodsi epicurei pericula, essent suscipit constituam ne mel. Minim eleifend repudiandae nec no. Vel omnium numquam ei, magna primis iudicabit eum cu. Viris nostro copiosae ex pro. Dicta urbanitas qui te, eu debitis inciderint duo.',
            'result' => 'Lorem ipsum dolor si ... erint duo.',
        ),
        'multibyte' => array(
            'orig'   => 'Λορεμ ιπσθμ δολορ σιτ αμετ, εξ νισλ αccθμσαν τινcιδθντ ιθσ, αν μθcιθσ φεθγιατ qθο, νεμορε φαcιλισισ διγνισσιμ εθ vελ. Εθμ ατ πθταντ οπτιον, ατ εοσ δομινγ δετραcτο. Θτ vισ αμετ φερρι αφφερτ, εστ ετ σολετ δεσερθισσε.',
            'result' => 'Λορεμ ιπσθμ δολορ σι ... εσερθισσε.',
        ),
        'multbyte_big' => array(
            'orig'   => 'Λορεμ ιπσθμ δολορ σιτ αμετ, εξ νισλ αccθμσαν τινcιδθντ ιθσ, αν μθcιθσ φεθγιατ qθο, νεμορε φαcιλισισ διγνισσιμ εθ vελ. Εθμ ατ πθταντ οπτιον, ατ εοσ δομινγ δετραcτο. Θτ vισ αμετ φερρι αφφερτ, εστ ετ σολετ δεσερθισσε. Μεα εα μολλισ ηαβεμθσ δελιcατισσιμι. Vισ εθ νιβη λιβεραvισσε. Ιδ διαμ ιλλθμ ηομερο εθμ, qθισ λιβερ εριπθιτ τε εαμ. Γραεcι vεριτθσ αλιενθμ θτ qθο, cθ qθι νεμορε ρεπθδιαρε, σιντ jθστο νολθισσε vελ αν.
Αμετ cλιτα σενσιβθσ vισ τε, μελιορε επιcθρι αβηορρεαντ αδ σεα. Διcαμ νθμqθαμ ασσεντιορ εθμ αν, εστ εξ τολλιτ vιvενδθμ ποσιδονιθμ. Μολεστιε σαλθτατθσ ηισ αδ, νιηιλ ηενδρεριτ qθι τε, ιθστο ποπθλο ηονεστατισ vελ τε. Εθ αθτεμ γλοριατθρ εαμ, vισ νιβη μθνδι μεντιτθμ νε, ομνισ διcαμ μαλθισσετ vιξ εθ.
Ατ πρι σεμπερ διcθντ αδvερσαριθμ. Νε ιισqθε δελεcτθσ qθο, τε ηενδρεριτ νεcεσσιτατιβθσ cθμ. Cθ νιβη μθcιθσ εθμ. Εσσε ομνεσqθε cθ εαμ. Σεα ει οδιο ερατ ιδqθε.
Μει νοvθμ λιβερ αδ, ατ vολθπτθα λαβοραμθσ μει. Cομμθνε φαcιλισι οπορτερε προ ιν. Qθι ιδ μοδο ηενδρεριτ νεcεσσιτατιβθσ, μει δενιqθε φαστιδιι vολθπτατθμ ει. Ατqθι διcαμ δεβιτισ ηασ τε, εραντ μενανδρι vολθπταρια τε νεc. Δθο vιρισ εξπλιcαρι ει, διcιτ τολλιτ ομιτταντθρ νεc εθ. Cθ ινvενιρε vιτθπερατοριβθσ εαμ, σεα νο σαλε μθνερε. Cθμ σιμθλ cομπλεcτιτθρ τε, σθμμο σιμθλ αλιενθμ εθμ νε, ομνισ τριτανι δετερρθισσετ νε vισ.
Λθδθσ περιcθλα ρεπριμιqθε εξ vελ, μθνδι σιμθλ ανcιλλαε cθ qθι, θσθ ιν φερρι σπλενδιδε. Νιηιλ πετεντιθμ ιδ vελ, ιθσ ετ μαλισ λαθδεμ. Τε νεc ανιμαλ ομιτταμ νομιναvι. Ταμqθαμ vολθπτθα ινδοcτθμ εστ εξ, ομνισ vενιαμ εσσεντ σεα εθ. Vιδε αλιενθμ γλοριατθρ αδ σεα, ιν μοδθσ λατινε οβλιqθε εστ.
Ιν λθδθσ περcιπιτ ιθσ, qθο λοβορτισ cομπρεηενσαμ αν. Αν δομινγ cομμθνε δεμοcριτθμ εθμ, νε εαμ φιερεντ ιντελλεγεβατ, vιμ ιδ cλιτα γραεcισ. Ερατ αccθμσαν εξ εοσ. Τατιον ιμπετθσ εθμ ατ, ετ ηισ σολθτα βλανδιτ. Φερρι ρεβθμ μαλθισσετ πρι ατ. Δολορθμ μενανδρι ρεπριμιqθε θσθ εθ, απεριαμ vεριτθσ μεα αν.
Σεδ ετ μθνδι τολλιτ cονστιτθτο. Εραντ αθδιρε ιμπεδιτ σιτ ετ, νο ηασ cονγθε vοcιβθσ vιτθπερατα. Ιν ερρορ σαλθτανδι σεδ, vελ θτ vιvενδο ρεcθσαβο πλατονεμ, ινvιδθντ ιντελλεγαμ vισ ιν. Θλλθμ φεθγαιτ νθσqθαμ προ τε, προμπτα αλβθcιθσ εθριπιδισ εθμ εα. Ιν σιτ τριτανι ασσεντιορ ποσιδονιθμ. Θτ vιρτθτε θρβανιτασ vιμ.
Εξ cθμ ερατ ιντεγρε φεθγιατ. Νε qθι νονθμυ vολθπτατιβθσ, μελ θτ αγαμ λατινε δολορεσ. Βονορθμ ατομορθμ νε θσθ, σολθτα νοστρθδ απειριαν ιδ εαμ. Θτ αππαρεατ περτιναcια cοντεντιονεσ ναμ, περφεcτο ηενδρεριτ ιδ cθμ. Νο qθασ νατθμ λοβορτισ ναμ.
Νεc διαμ σονετ περcιπιτθρ αν, νεc vιδερερ βονορθμ φαβθλασ αδ. Cορπορα φορενσιβθσ cθμ θτ, μει εξ αccθσαμ πρινcιπεσ. Πορρο cετεροσ νε σεα, τολλιτ ατομορθμ ρεφορμιδανσ ηασ ιν. Ιδ φαcετε αδμοδθμ vισ, εστ θτ διcτασ αππαρεατ, εvερτι προβατθσ πλατονεμ προ θτ. Εθμ αν νονθμυ νολθισσε εξπετενδισ, εξ σεδ ποστθλαντ τορqθατοσ, ναμ φεθγιατ πονδερθμ νο. Νε πρι φιερεντ ανcιλλαε δελεcτθσ, qθανδο γλοριατθρ εθμ ιν, τε ερθδιτι φαβθλασ σcριπτορεμ vισ.
Νε φθγιτ vιvενδο αλιενθμ θσθ, σεδ ρεπριμιqθε ινστρθcτιορ εθ. Vιξ ερατ cονσθλ αππετερε αδ. Vελιτ ινστρθcτιορ εθ νεc. Αδ ιθσ ινιμιcθσ πετεντιθμ, vισ ταμqθαμ δολορθμ σαπιεντεμ ατ, δενιqθε φιερεντ ιθσ.',
            'result' => 'Λορεμ ιπσθμ δολορ σι ... ερεντ ιθσ.',
        ),
        'mutibyte_short' => array(
          'orig'  => 'Καλησπέρα',
          'result' => 'Καλησπέρα'
        ),
        'with URL' => array(
          'orig'  => '<a href="https://www.phplist.com">phpList</a>',
          'result' => '<a href="https://www ... hpList</a>'
        ),
        'exactly30' => array(
          'orig'  => '012345678901234567890123456789',
          'result' => '012345678901234567890123456789'
        ),
        'exactly31' => array(
          'orig'  => '0123456789012345678901234567891',
          'result' => '01234567890123456789 ... 1234567891'
        )
    );

    public function runtest()
    {
        if (!function_exists('shortenText')) {
          echo s('function not available to test').'<br/>';
          return;
        }
        $pass = 1;
        foreach ($this->tests as $test) {
            $result = shortenText($test['orig']);
            echo htmlspecialchars($result).' <strong>should be</strong> '.htmlspecialchars($test['result']);
            $pass = $pass && trim($result) == trim($test['result']);
            if (trim($result) == trim($test['result'])) {
                echo $GLOBALS['img_tick'];
            } else {
                echo $GLOBALS['img_cross'];
            }
            echo '<br/>';
        }

        return $pass;
    }
}
