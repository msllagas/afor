import type { Workspace } from "@/types";

export interface Inviter {
    id: string,
    name: string,
}

export interface Invitation {
    id: string,
    workspace: Workspace,
    inviter: Inviter,
}
