import { Routes } from '@angular/router';
import { adminGuard, authGuard, guestGuard, permissionGuard } from './core/auth.guard';

export const routes: Routes = [
	{
		path: 'login',
		canActivate: [guestGuard],
		loadComponent: () => import('./pages/login-page.component').then((m) => m.LoginPageComponent),
	},
	{
		path: 'dashboard',
		canActivate: [authGuard],
		loadComponent: () => import('./pages/dashboard-page.component').then((m) => m.DashboardPageComponent),
	},
	{
		path: 'clients',
		canActivate: [authGuard, permissionGuard('clients.view')],
		loadComponent: () => import('./pages/clients-page.component').then((m) => m.ClientsPageComponent),
	},
	{
		path: 'cases',
		canActivate: [authGuard, permissionGuard('cases.view')],
		loadComponent: () => import('./pages/cases-page.component').then((m) => m.CasesPageComponent),
	},
	{
		path: 'governance',
		canActivate: [authGuard, permissionGuard('directors.view')],
		loadComponent: () => import('./pages/governance-page.component').then((m) => m.GovernancePageComponent),
	},
	{
		path: 'incidents',
		canActivate: [authGuard, permissionGuard('incidents.view')],
		loadComponent: () => import('./pages/incidents-page.component').then((m) => m.IncidentsPageComponent),
	},
	{
		path: 'tasks',
		canActivate: [authGuard, permissionGuard('tasks.view')],
		loadComponent: () => import('./pages/tasks-page.component').then((m) => m.TasksPageComponent),
	},
	{
		path: 'communications',
		canActivate: [authGuard, permissionGuard('communications.view')],
		loadComponent: () => import('./pages/communications-page.component').then((m) => m.CommunicationsPageComponent),
	},
	{
		path: 'audit-logs',
		canActivate: [authGuard, permissionGuard('audit_logs.view')],
		loadComponent: () => import('./pages/audit-logs-page.component').then((m) => m.AuditLogsPageComponent),
	},
	{
		path: 'roles',
		canActivate: [authGuard, adminGuard, permissionGuard('roles.view')],
		loadComponent: () => import('./pages/roles-page.component').then((m) => m.RolesPageComponent),
	},
	{
		path: 'compliance-automation',
		canActivate: [authGuard, permissionGuard('roles.view')],
		loadComponent: () => import('./pages/compliance-automation-page.component').then((m) => m.ComplianceAutomationPageComponent),
	},
	{
		path: 'companies',
		canActivate: [authGuard, permissionGuard('companies.view')],
		loadComponent: () => import('./pages/companies-page.component').then((m) => m.CompaniesPageComponent),
	},
	{ path: '', pathMatch: 'full', redirectTo: 'login' },
	{ path: '**', redirectTo: 'login' },
];
