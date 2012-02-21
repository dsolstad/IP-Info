<?php

/*
 * Gets information about an IP address with subnet mask
 * Author: echofish (echofish.org)
 * Last updated: 18 Feb 2012
 *
 * Example of use:
 *   $ip = new IPInfo("172.16.35.233", 18);
 *   $ip->show_all();
 *
 */

class InvalidIpClassException extends Exception { }
class InvalidIpAddrException extends Exception { }
class InvalidBinOpsException extends Exception { }
class InvalidPrivAddrRangeException extends Exception { }

class IPInfo {

    private $ip_addr;                 // String, ex. 198.168.0.1
    private $ip_addr_class;           // String with char A to E
    private $is_private;              // True/False if addr is in priv range
    private $private_addr_range;      // Array with the private class range
    private $host;                    // String, ex. 0.0.0.1
    private $network;                 // String, ex. 192.168.0.0
    private $broadcast;               // String, ex. 192.168.255.255
    private $numb_hosts;              // Integer, ex. 65536
    private $subnet_mask_default;     // String, ex. 255.255.0.0
    private $subnet_mask_default_alt; // Integer, ex. 24
    private $subnet_mask_custom;      // String, ex. 255.255.192.0
    private $subnet_mask_custom_alt;  // Integer, ex. 18
    private $subnet_mask_inv;         // String, ex. 0.0.255.255
    private $bits_borrowed;           // Integer, bits borrowed from def. mask
    private $information;             // String, additional information

    public function __construct($ip_addr, $mask=0) {
        $this->setIpAddr($ip_addr);
        $this->setSubnetMaskCustom($mask);
    }

    public function getIpAddr() { 
        return $this->ip_addr; 
    }

    public function getIpAddrClass() { 
        return $this->ip_addr_class; 
    }

    public function getIsPrivate() {
        return $this->is_private;
    }

    public function getHost() {
        return $this->host;
    }

    public function getNetwork() {
        return $this->network;
    }

    public function getBroadcast() {
        return $this->broadcast;
    }

    public function getSubnetMaskDefault() { 
        return $this->subnet_mask_default;
    }

    public function getSubnetMaskCustom() { 
        return $this->subnet_mask_custom;
    }

    public function getSubnetMaskInverted() {
        return $this->subnet_mask_inv;
    }

    public function getSubnetMaskDefaultAlt() {
        return $this->subnet_mask_default_alt;
    }

    public function getSubnetMaskCustomAlt() {
        return $this->subnet_mask_custom_alt;
    }

    public function getBitsBorrowed() {
        return $this->bits_borrowed;
    }

    public function getNumbHosts() {
        return $this->numb_hosts;
    }

    public function getInformation() {
        return $this->information;
    }

    public function getPrivateAddrRange() {
        return $this->private_addr_range;
    }

    private function setPrivateAddrRange() {
        $range['A'] = array('start' => array(10,0,0,0),
                            'stop' => array(10,255,255,255));
        $range['B'] = array('start' => array(172,16,0,0),
                            'stop' => array(172,31,255,255));
        $range['C'] = array('start' => array(192,168,0,0),
                             'stop' => array(192,168,255,255));
        $class = strtoupper($this->getIpAddrClass());
        if (in_array($class, range('A', 'C'))) {
            $this->private_addr_range = $range[$class];
        } else {
            $msg = "Invalid private IP addr class. (A-C)";
            throw new InvalidPrivAddrRangeException($msg);
            return false;
        }
    }

    public function setIpAddr($addr) {
        if ($this->validAddrPattern($addr)) {
            $this->ip_addr = $addr;
            $this->setIpAddrClass();
            $this->setSubnetMaskDefault();
            $this->setSubnetMaskDefaultAlt();
        } else {
            $msg = "Invalid IP address pattern (255.255.255.255)";
            throw new InvalidIpAddrException($msg);
        }
    }

    // Sat to true if the address in in a private range
    private function setPrivate($bool) {
        $this->is_private = $bool;
    }

    // Sets the custom subnet mask. 
    //  Takes both standard format (255.255.255.0) and alt. (24)
    public function setSubnetMaskCustom($mask) {
        $binary = '';
        // Checks if the mask is in the short format e.x: /26
        //   and converts it to traditional format.
        if (is_numeric($mask) && $mask <= 32 && $mask >= 8) {
            for ($i = 0; $i < $mask; $i++) {
                $binary .= '1';
            }
            for ($i = 32 - $mask; $i > 0; $i--) {
                $binary .= '0';
            }
            $this->subnet_mask_custom = $this->IPbin2dec($binary);
        // Checks if the mask is in a traditional format e.x: 255.255.0.0
        } elseif ($this->validAddrPattern($mask)) {
            $this->subnet_mask_custom = $mask;
        // If the custom mask is empty, then we set it equal to the default 
        } else {
            $this->subnet_mask_custom = $this->getSubnetMaskDefault();
        }
        $this->setSubnetMaskCustomAlt();
        $this->setSubnetMaskInverted();
        $this->setHost();
        $this->setNetwork();
        $this->setBroadcast();
        $this->setNumbHosts();
        $this->setBitsBorrowed();
    }

    // Sets the default subnet mask based on the IP address class
    private function setSubnetMaskDefault() {
        if ($this->getIpAddrClass()) {
            if ($this->getIpAddrClass() == 'A') {
                $this->subnet_mask_default = "255.0.0.0";
            } elseif ($this->getIpAddrClass() == 'B') {
                $this->subnet_mask_default = "255.255.0.0";
            } elseif ($this->getIpAddrClass() == 'C') {
                $this->subnet_mask_default = "255.255.255.0";
            } else {
                $this->subnet_mask_default = "0.0.0.0";
            }
        } else {
            $msg = "IP address class not sat";
            throw new InvalidIpClassException($msg);
            return false;
        }
    }

    // Sets the total amout of nodes/hosts incl. network and broadcast
    private function setNumbHosts() {
        $this->numb_hosts = pow(2, 32 - $this->getSubnetMaskCustomAlt());
    }

    // Sets the alternative default subnet mask in integer based on four octets
    private function setSubnetMaskDefaultAlt() {
        $alt = strlen(str_replace('0', '', $this->IPdec2bin($this->getSubnetMaskDefault())));
        $this->subnet_mask_default_alt = $alt; 
    }

    // Sets the alternative custom subnet mask in integer based on four octets
    private function setSubnetMaskCustomAlt() {
        $alt = strlen(str_replace('0', '', $this->IPdec2bin($this->getSubnetmaskCustom())));
        $this->subnet_mask_custom_alt = $alt;
    }

    // Inverts a string of binary numbers (1111111100000000 -> 0000000011111111)
    private function setSubnetMaskInverted() {
        $inverted = $this->IPdec2bin($this->getSubnetMaskCustom());
        $inverted = str_replace('0', 'a', $inverted);
        $inverted = str_replace('1', '0', $inverted);
        $inverted = str_replace('a', '1', $inverted);
        $this->subnet_mask_inv =  $this->IPbin2dec($inverted);
    }

    // Sets the class of the IP address, 
    //   and also tries to find out if the address is in a private space
    private function setIpAddrClass() {
        $octs = explode('.', $this->getIpAddr());
        if ($octs[0] >= 1 && $octs[0] <= 127) {
            $this->ip_addr_class = 'A';
            if ($octs[0] == 10) {
                $this->setPrivate(true);
            }
        } elseif ($octs[0] >= 128 && $octs[0] <= 191) {
            $this->ip_addr_class = 'B';
            if ($octs[0] == 172 && $octs[1] >= 16 && $octs[1] <= 31) {
                $this->setPrivate(true);
            }
        } elseif ($octs[0] >= 192 && $octs[0] <= 223) {
            $this->ip_addr_class = 'C';
            if ($octs[0] == 192 && $octs[1] == 168) {
                $this->setPrivate(true);
            }
        } elseif ($octs[0] >= 224 && $octs[0] <= 239) {
            $this->ip_addr_class = 'D';
        } elseif ($octs[0] >= 240 && $octs[0] <= 255) {
            $this->ip_addr_class = 'E';
        }
        if ($this->getIsPrivate()) {
            $this->setPrivateAddrRange();
        }
        // Sets information based on the IP class and network
        $this->setInformation();
    }

    // IP address AND Custom subnet mask
    private function setNetwork() {
        $this->network = $this->binOps($this->getIpAddr(), 
                                       $this->getSubnetMaskCustom(), 'AND');
    }
 
    // IP address AND Inverted custom subnet mask
    private function setHost() {
        $this->host = $this->binOps($this->getIpAddr(), 
                                    $this->getSubnetMaskInverted(), 'AND');
    }

    // Network address XOR Inverted custom subnet mask
    private function setBroadcast() {
        $this->broadcast = $this->binOps($this->getNetwork(), 
                                         $this->getSubnetMaskInverted(), 'XOR');
    }

    // Amount of bits borrowed to make custom subnet mask
    private function setBitsBorrowed() {
        $xor = $this->binOps($this->getSubnetMaskDefault(), 
                             $this->getSubnetMaskCustom(), 'XOR');
        $this->bits_borrowed = strlen(str_replace('0', '', $this->IPdec2bin($xor)));
    }

    // Sets additional information about the IP addr
    private function setInformation() {
        if ($this->getIpAddrClass() == 'D') {
            $info = "Reserved for multicast";
        } elseif ($this->getIpAddrClass() == 'E') {
            $info = "Reserved for experimental, used for research";
        } elseif (array_shift(explode('.', $this->getIpAddr())) == 127) {
            $info = "Network 127 is reserved for loopback and internal testing";
        } else {
            $info = "";
        }
        $this->information = $info;
    }

    // Takes two addresses in string format and perform a binary operation
    private function binOps($addr1, $addr2, $op) {
        $op = strtoupper($op);
        $a1 = explode('.', $addr1);
        $a2 = explode('.', $addr2);
        if (!$this->validAddrPattern($addr1) || !$this->validAddrPattern($addr2)) {
            $msg = "Wrong format. (255.255.255.255)" . $addr1 . "-".$addr2;
            throw new InvalidBinOpsException($msg);
            return false;
        }
        if (count($a1) != count($a2)) {
            $msg = "Binary sizes doesn't match";
            throw new InvalidBinOpsException($msg);
            return false;
        }
        if (!in_array($op, array('AND', 'XOR'))) {
            $msg = "Invalid operator.";
            throw new InvalidBinOpsException($msg);
            return false;
        }
        $res = array();
        switch ($op) {
            case 'AND':
                for ($i = 0; $i <= count($a1) - 1; $i++)
                    $res[] = chr($a1[$i]) & chr($a2[$i]);
                break;
            case 'XOR':
                for ($i = 0; $i <= count($a1) - 1; $i++)
                    $res[] = chr($a1[$i]) ^ chr($a2[$i]);
                break;
        }
        return join('.', array_map('ord', $res));
    }

    // Checks if an IP address in string format is valid.
    private static function validAddrPattern($addr) {
        if (ip2long($addr) && ip2long($addr) != -1) {
            return true;
        } else {
            return false;
        }
    }

    // Converts a string of binary numbers to string of octets in decimals
    private static function IPbin2dec($bin) {
        return join('.', array_map('bindec', explode("\n", trim(chunk_split($bin, 8, "\n")))));
    }

    // Converts a string of octets in decimals to a string of binary numbers
    private static function IPdec2bin($dec) {
        $bin = '';
        foreach (explode('.', $dec) as $oct) {
            $bin .= str_pad(decbin($oct), 8, '0', STR_PAD_LEFT);
        }
        return $bin;
    }

    // Shows all the information. Obviously...
    public function show_all($html=false) {
        $out = '';
        $out .= "IP address: " . $this->getIpAddr() . "\n";
        $out .= "Subnet Mask Default: ";
        $out .= $this->getSubnetMaskDefault();
        $out .= " (/" . $this->getSubnetMaskDefaultAlt() . ")\n";
        $out .= "Subnet Mask Custom: ";
        $out .= $this->getSubnetMaskCustom(); 
        $out .= " (/" . $this->getSubnetMaskCustomAlt() . ")\n";
        $out .= "Class: " . $this->getIpAddrClass();
        $out .= "\nThe address is " . ($this->getIsPrivate()? "":"not ") . "private\n";
        if ($this->getIsPrivate()) {
            $out .= "Private range: ";
            if ($class = $this->getIpAddrClass()) {
                $range = $this->getPrivateAddrRange();
                $out .= join('.', $range['start']) . " to " .
                         join('.', $range['stop']) . "\n";
            }
        }
        $out .= "Network: " . $this->getNetwork() . "\n";
        $out .= "Host: " . $this->getHost() . "\n";
        $out .= "Broadcast: " . $this->getBroadcast() . "\n";
        $out .= "Number of hosts: " . $this->getNumbHosts() . " (incl. host and network addresses)\n";
        $out .= "Bits borrowed in custom subnet mask: " . $this->getBitsBorrowed() . "\n";;
        $out .= $this->getInformation() . "\n\n";
        print ($html? nl2br($out) : $out);
    }

}

?>
