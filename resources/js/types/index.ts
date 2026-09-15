export interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions?: string[];
}

export interface Tenant {
    id: string;
    name: string;
    slug: string;
    status: 'trial' | 'active' | 'suspended';
    plan: string | null;
    trial_ends_at: string | null;
    domains?: Domain[];
    created_at: string;
    updated_at: string;
}

export interface Domain {
    id: number;
    domain: string;
    tenant_id: string;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    tenant: {
        id: string;
        name: string;
        slug: string;
        plan: string | null;
    } | null;
    flash: {
        success: string | null;
        error: string | null;
    };
    [key: string]: unknown;
}
