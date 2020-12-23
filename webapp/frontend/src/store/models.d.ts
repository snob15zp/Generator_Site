export interface UserJson {
    id: String;
    token: String;
    privileges: String[];
    profile: UserProfileJson;
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
}

export interface ProgramJson {
    id: string;
    name: string;
    hash: string;
    created_at: string;
}

export interface User {
    id: String;
    token: String;
    name: String;
    privileges: String[];
    profile: UserProfile;
}

export interface UserCredetials {
    login: String;
    password: String;
}

export interface ErrorReponse {
    code: Number;
    message?: String;
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
}

export interface UploadFileRequest {
    file: File;
    folder: Folder;
    onProgressCallback: (_: number) => void;
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

export interface ErrorJson {
    message: string;
    status: number;
}

export interface ErrorResponseJson {
    errors: ErrorJson;
}