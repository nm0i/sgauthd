#!/usr/bin/tclsh

set SGPath {/s/sg/lib}

cd "${SGPath}/../var/authd"

gets stdin user
gets stdin passwd

if {! [regexp {^[a-z]+$} ${user}]} {
    puts "ERROR,incorrect username"
    exit
}

set userFile "${SGPath}/adm/save/users/[string range $user 0 0]/$user.o"
if {! [file exists "$userFile"]} {
    puts "ERROR,wrong password or username"
    exit
}

catch {set fp [open $userFile r]}
set contents [read -nonewline $fp]
close $fp
set splitCont [split $contents "\n"]
foreach elem $splitCont {
    regexp {^password "(.*)"} $elem -> hash
    regexp {^position "(.*)"} $elem -> position
    regexp {^email "(.*)"} $elem -> email
}

if {! [info exists hash]} {
    puts "ERROR,wrong password or username"
    exit
}

if {! [info exists position]} {
    set position "player"
}

if {! [info exists email]} {
    set email "law@shadowgate.org"
}

load [file join [pwd] crypt.so]

if {$passwd == "info"} {
    puts "INFO,$position,$email"
    exit
}

set nhash [crypt "$passwd" "$hash"]

if {[string equal $nhash $hash]} {
    puts "OK,$position,$email"
    exit
} else {
    puts "ERROR,wrong password or username"
    exit
}
