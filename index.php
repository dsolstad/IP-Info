<?php

require_once("./ipinfo.class.php");

if (isset($_POST['submit'])) {
    try {
        $addr = $_POST['addr'];
        if (!empty($_POST['submask'])) {
            $mask = $_POST['submask'];
        } elseif (!empty($_POST['submask_alt'])) {
            $mask = $_POST['submask_alt'];
        } else {
            $mask = 0;
        }
        $ip = new IPInfo($addr, $mask);
        $ip->show_all($html=true);
    } catch (InvalidAddrIpAddrException $e) {
        print "Caught InvalidIpAddrException ('" . $e->getMessage() . "')\n";
    } catch (InvalidIpClassException $e) {
        print "Caught InvalidIpClassException ('" . $e->getMessage() . "')\n";
    } catch (InvalidBinOpsException $e) {
        print "Caught InvalidPrivAddrRangeException ('" . $e->getMessage() . "')\n";
    } catch (InvalidPrivAddrRangeException $e) {
        print "Caught InvalidPrivAddrRangeException ('" . $e->getMessage() . "')\n";
    }
}

?>
<html>
<body>
<form action="" method="post">
    IP address: <input name="addr" type="text" placeholder="192.168.0.1" /><br />
    Subnet mask: <input name="submask" type="text" placeholder="255.255.0.0" /> or 
                 <input name="submask_alt" type="text" placeholder="26" /><br />
                 <input name="submit" type="submit" value="submit" />
</form>
</body>
</html>
