import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    flash?: {
        success?: string | null;
        status?: string | null;
    };
    notifications?: {
        unreadCount: number;
    };
    auth: Auth;
    [key: string]: unknown;
}

export interface AuthenticatedSharedData extends SharedData {
    auth: {
        user: User;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    phone_verified_at?: string | null;
    created_at: string;
    updated_at: string;
    role_names?: string[];
    primary_role?: string | null;
    position?: string | null;
    permissions?: string[];
    primary_goal?: string | null;
    preferred_contact_method?: string | null;
    registration_channel?: string | null;
    landing_path?: string | null;
    [key: string]: unknown; // This allows for additional properties...
}
