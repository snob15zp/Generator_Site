import {PagingRequest} from "@/store/models";

function queryFromPagingRequest(pagingRequest: PagingRequest): string {
    const filter = pagingRequest.filter;
    let ownerId, roles;
    if (filter) {
        ownerId = filter.owner === null ? -1 : filter.owner?.id;
        roles = filter.roles?.map(role => `roles[]=${role.name}`).join('&');
    }

    const sort = pagingRequest.sortBy.map((item, index) =>
        `sortBy[]=${item}&sortDir[]=${pagingRequest.sortDesc[index] ? 'asc' : 'desc'}`).join('&');

    return `perPage=${pagingRequest.itemsPerPage}` +
        `&page=${pagingRequest.page}` +
        `${pagingRequest.query ? `&query=${pagingRequest.query}` : ''}` +
        `${sort ? `&${sort}` : ''}` +
        `${roles ? `&${roles}` : ''}` +
        `${(ownerId ? `&owner_id=${ownerId}` : '')}` +
        `${filter?.unlinked === true ? `&unlinked` : ''}`;
}

export default {
    queryFromPagingRequest
}