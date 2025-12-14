const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue') },
      { path: 'campaigns', component: () => import('pages/CampaignsPage.vue') },
      { path: 'leads', component: () => import('pages/LeadsPage.vue') },
      { path: 'leads/:id/conversations', component: () => import('pages/LeadConversationsPage.vue') },
      { path: 'kanban', component: () => import('pages/KanbanPage.vue') },
    ],
  },
  {
    path: '/login',
    component: () => import('layouts/LoginLayout.vue'),
    children: [
      { path: '', component: () => import('pages/LoginPage.vue') }
    ]
  },
  {
    path: '/auth/facebook/callback',
    component: () => import('pages/FacebookCallbackPage.vue'),
    meta: { public: true }
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
