proc verifyPass {user passwd} {
    set SGPath {/s/sg/lib}
    cd "${SGPath}/../bin/sgauthd"

    if {! [regexp {^[a-z]+$} ${user}]} {
        return "ERROR,incorrect username"
    }

    set userFile "${SGPath}/adm/save/users/[string range $user 0 0]/$user.o"
    if {! [file exists "$userFile"]} {
        return "ERROR,wrong password or username"
    }

    catch {set fp [open $userFile r]}
    set contents [read -nonewline $fp]
    close $fp
    set splitCont [split $contents "\n"]

    foreach elem $splitCont {
        regexp {^password "(.*)"} $elem -> hash
    }

    if {! [info exists hash]} {
        return "ERROR,wrong password or username"
    }

    if {! [info exists position]} {
        set position "player"
    }

    if {! [info exists email]} {
        set email "law@shadowgate.org"
    }

    # Dokuwiki userinfo interface.
    if {$passwd == "info"} {
        return "INFO,$position,$email"
        exit
    }

    load [file join [pwd] crypt.so]

    set nhash [crypt "$passwd" "$hash"]

    if {[string equal $nhash $hash]} {
        return "OK,$position,$email"
    } else {
        return "ERROR,wrong password or username"
    }
}
