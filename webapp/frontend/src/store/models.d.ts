import {Role} from "@/store/modules/user";
import {CancelTokenSource} from "axios";
import {UserFilter} from "@/service/api/userService";

export interface UserJson {
    id: string;
    token: string;
    privileges: string[];
    password?: string;
    profile: UserProfileJson;
    role: string;
    created_at?: string;
    updated_at?: string;
}

export interface UserProfileJson {
    id?: string;
    user: UserJson;
    name: string;
    surname?: string;
    phone_number?: string;
    address?: string;
    date_of_birth?: string;
    email: string;
    password?: string;
    created_at?: string;
    updated_at?: string;
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
    active?: boolean;
}

export interface ProgramJson {
    id: string;
    name: string;
    hash: string;
    created_at: string;
    owner: UserJson;
}

export interface User {
    id: string;
    token: string;
    privileges: string[];
    password?: string,
    profile: UserProfile;
    role: Role;
    owner?: User;
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
    filter?: UserFilter;
}

export interface PagingResponse<R> {
    total: number;
    data: Array<R>;
}

export interface UserProfile {
    id?: string;
    name: string;
    surname: string;
    phoneNumber: string;
    address: string;
    dateOfBirth: Date | null;
    email: string;
    createdAt?: Date;
    updatedAt?: Date;
}

export interface Program {
    id: string;
    name: string;
    createdAt?: Date;
    owner?: User | null;
}

export interface Folder {
    id: string | null;
    name: string;
    expiredAt: Date;
    createdAt?: Date;
    isEncrypted: boolean;
    active: boolean;
}

export interface UploadFileRequest {
    files: File[];
    owner: User;
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
    created_at: string;
    files: FirmwareFilesJson[];
}

export interface FirmwareFilesJson {
    name: string;
}

export interface SoftwareJson {
    id: string;
    active: boolean;
    version: string;
    created_at: string;
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
    code: number;
    data?: any;
}

export interface ErrorResponseJson {
    errors: ErrorJson;
}