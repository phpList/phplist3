<?php
/**
 * @author Xheni Myrtaj <xheni@phplist.com>
 * @ToDo logo
 *
 */

class vCard
{
    /**
     * @var string
     */
    private $org;
    /**
     * @var string
     */
    private $email;
    /**
     * @var
     */
    private $logo;
    /**
     * @var string
     */
    private $url;


    /**
     * @param string $org
     */
    public function setOrg($org){
        $this->org = $org;
    }

    /**
     * @return string
     */
    public function getOrg()
    {
        return $this->org;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @param mixed $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Force VCard download (.vcf)
     *
     * @return void
     */
    public function createVCard()
    {
        header('Content-Description: File Transfer');
        header('Content-Type: text/vcard');
        header('Content-Disposition: attachment; filename="' . $this->getOrg() .'-contact.vcf" ');
        header('Pragma: public');
        ob_clean();
        $vCard = "BEGIN:VCARD\r\n";
        $vCard .= "VERSION:3.0\r\n";
        $vCard .= "ORG:" . $this->getOrg() . "\r\n";
        $vCard .= "EMAIL:". $this->getEmail() . "\r\n";
        $vCard .= "URL:" . $this ->getUrl() . "\r\n";
        $vCard .= "REV:" . date("Y-m-d") . "T" . date("H:i:s") . "\r\n";
        $vCard .= "END:VCARD\r\n";
        echo $vCard;
    }
}

