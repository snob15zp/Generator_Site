export interface User {
  token: String;
  name: String;
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
  hash?: string;
  name: string;
  surname: string;
  phoneNumber: string;
  address: string;
  dateOfBirth: Date | null;
  email: string;
  createdAt?: Date;
  modifiedAt?: Date;
}

export interface Program {
  hash: string;
  path: string;
  name: string;
}

export interface Folder {
  hash: string | null;
  path: string;
  name: string;
  expiredAt: Date;
}
