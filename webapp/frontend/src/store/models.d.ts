export interface UserJson {
  id: String;
  token: String;
  privileges: Stringp[];
  profile: UserProfileJson;
}

export interface UserProfileJson {
  id?: string;
  user_id: string;
  name: string;
  surname?: string;
  phone_number?: string;
  addres?: string;
  date_of_birth?: string;
  email: string;
  created_at: string;
  updated_at?: string;
}

export interface User {
  id: String;
  token: String;
  name: String;
  privileges: Stringp[];
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
