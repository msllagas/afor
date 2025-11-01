import type { Workspace } from "@/types";

export interface Inviter {
    id: string,
    name: string,
}

export interface Invitation {
    token: string,
    invited_by: string,
    workspace: Workspace,
    inviter: Inviter,
}
