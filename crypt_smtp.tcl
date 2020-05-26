#!/usr/bin/tclsh

package require base64

proc verifyPass {user passwd} {
    set SGPath {/s/sg/lib}

    cd "${SGPath}/../var/authd"

    if {! [regexp {^[a-z]+$} ${user}]} {
        return 0
    }

    set userFile "${SGPath}/adm/save/users/[string range $user 0 0]/$user.o"
    if {! [file exists "$userFile"]} {
        return 0
    }

    catch {set fp [open $userFile r]}
    set contents [read -nonewline $fp]
    close $fp
    set splitCont [split $contents "\n"]

    foreach elem $splitCont {
        regexp {^password "(.*)"} $elem -> hash
    }

    if {! [info exists hash]} {
        return 0
    }

    load [file join [pwd] crypt.so]

    set nhash [crypt "$passwd" "$hash"]

    if {[string equal $nhash $hash]} {
        return 1
    } else {
        return 0
    }
}

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
        if {[verifyPass $uname $upass]} {
            puts "235 2.7.0 Authentication successful"
            exit
        } else {
            exit
        }
    } else {
        puts "250-Whatever"
    }
}
