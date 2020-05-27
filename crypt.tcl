#!/usr/bin/tclsh

gets stdin user
gets stdin passwd

source [file join [pwd] passverify.tcl]

puts [verifyPass $user $passwd]
