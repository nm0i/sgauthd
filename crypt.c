#include <tcl.h>
#include <stdio.h>
#include <crypt.h>

static int cryptCmd(ClientData clientData, Tcl_Interp *interp, int objc, Tcl_Obj *const objv[])
{
    if (objc < 3)
    {
        Tcl_AddErrorInfo(interp, "crypt key salt\n");
        return TCL_ERROR;
    }
    Tcl_SetObjResult(interp, Tcl_NewStringObj(
                         crypt(Tcl_GetString(objv[1]),
                               Tcl_GetString(objv[2])), -1));
    return TCL_OK;
}

int Crypt_Init(Tcl_Interp *interp)
{
    if (Tcl_InitStubs(interp, TCL_VERSION, 0) == NULL) {
        return TCL_ERROR;
    }
    Tcl_CreateObjCommand(interp, "crypt", cryptCmd, NULL, NULL);
    return TCL_OK;
}

