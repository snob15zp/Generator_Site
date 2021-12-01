import {Role} from "@/store/modules/user";
import {CancelTokenSource} from "axios";

export interface UserJson {
    id: string;
    token: string;
    privileges: string[];
    profile: UserProfileJson;
    role: string;
}

export interface UserProfileJson {
    id?: string;
    user_id: string;
    name: string;
    surname?: string;
    phone_number?: string;
    address?: string;
    date_of_birth?: string;
    email: string;
    created_at: string;
    updated_at?: string;
    password?: string;
}

export interface Link {
    direction: string;
    url: string;
}

export interface PagingMeta {
    current_page: number;
    from: number;
    to: number;
    last_page: number;
    total: number;
}

export interface PagingResponseJson<T> {
    data: T[];
    links: Link[];
    meta: PagingMeta;
}

export interface FolderJson {
    id: string | null;
    name: string;
    expires_in: number;
    created_at?: string;
    is_encrypted?: boolean;
}

export interface ProgramJson {
    id: string;
    name: string;
    hash: string;
    created_at: string;
}

export interface User {
    id: string;
    token: string;
    name: string;
    privileges: string[];
    profile: UserProfile;
    role: Role;
}

export interface UserCredentials {
    login: string;
    password: string;
}

export interface ErrorResponse {
    code: Number;
    message?: string;
}

export interface PagingRequest {
    page: number;
    itemsPerPage: number;
    sortBy: string[];
    sortDesc: boolean[];
    query: string;
}

export interface PagingResponse<R> {
    total: number;
    data: Array<R>;
}

export interface UserProfile {
    id?: string;
    userId?: string;
    name: string;
    surname: string;
    phoneNumber: string;
    address: string;
    dateOfBirth: Date | null;
    email: string;
    createdAt?: Date;
    updatedAt?: Date;
    password?: string;
    role?: Role;
}

export interface Program {
    id: string;
    name: string;
}

export interface Folder {
    id: string | null;
    name: string;
    expiredAt: Date;
    createdAt?: Date;
    isEncrypted: boolean;
}

export interface UploadFileRequest {
    files: File[];
    folder?: Folder;
    onProgressCallback: (_: number) => void;
    cancelSource?: CancelTokenSource
}

export interface DownloadFileRequest {
    program: Program;
    onProgressCallback: (_: number) => void;
}

export interface SaveFolderRequest {
    folder: Folder;
    userProfile: UserProfile;
}

export interface Firmware {
    id: string;
    active: boolean;
    version: string;
    createdAt: Date;
    files: FirmwareFiles[];
}

export interface FirmwareFiles {
    fileName: string;
}

export interface FirmwareJson {
    id: string;
    active: boolean;
    version: string;
    createdAt: string;
    files: FirmwareFilesJson[];
}

export interface FirmwareFilesJson {
    name: string;
}

export interface SoftwareJson {
    id: string;
    active: boolean;
    version: string;
    createdAt: string;
    file: string;
    fileUrl: string;
}

export interface Software {
    id: string;
    active: boolean;
    version: string;
    createdAt: Date;
    file: string;
    fileUrl: string;
}

export interface ErrorJson {
    message: string;
    status: number;
}

export interface ErrorResponseJson {
    errors: ErrorJson;
}