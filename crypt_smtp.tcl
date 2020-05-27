#!/usr/bin/tclsh

package require base64

source [file join [pwd] passverify.tcl]

set in_auth 0

puts "220 shadowgate.org ESMTP SMTPSGAUTHD"
while {[gets stdin line] >= 0} {
    if {[regexp "^EHLO" $line]} {
        puts "250-shadowgate.org"
        puts "250 AUTH LOGIN"
    } elseif {[regexp "^AUTH LOGIN" $line]} {
        set in_auth 1
        puts "334 [::base64::encode Username:]"
    } elseif {$in_auth==1} {
        set uname [::base64::decode $line]
        set in_auth 2
        puts "334 [::base64::encode Password:]"
    } elseif {$in_auth==2} {
        set upass [::base64::decode $line]
        if {[regexp "^OK" [verifyPass $uname $upass]]} {
            puts "235 2.7.0 Authentication successful"
            exit
        } else {
            exit
        }
    } else {
        puts "250-Whatever"
    }
}
