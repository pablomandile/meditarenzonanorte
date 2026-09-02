<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { CalendarDays, CalendarRange, Database, ExternalLink, FileText, GraduationCap, HelpCircle, Images, Settings } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Páginas',
        href: '/admin/pages',
        icon: FileText,
    },
    {
        title: 'Eventos',
        href: '/admin/events',
        icon: CalendarDays,
    },
    {
        title: 'Calendario',
        href: '/admin/calendar',
        icon: CalendarRange,
    },
    {
        title: 'Galería',
        href: '/admin/gallery',
        icon: Images,
    },
    {
        title: 'Preguntas frecuentes',
        href: '/admin/faqs',
        icon: HelpCircle,
    },
    {
        title: 'Datos recurrentes',
        href: '/admin/datos-recurrentes',
        icon: Database,
    },
    {
        title: 'Ajustes del sitio',
        href: '/admin/settings',
        icon: Settings,
    },
];

const helpNavItems: NavItem[] = [
    {
        title: 'Tutoriales',
        href: '/admin/tutorials',
        icon: GraduationCap,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Ver sitio',
        href: '/',
        icon: ExternalLink,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/admin/pages">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain :items="helpNavItems" label="Ayuda" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
