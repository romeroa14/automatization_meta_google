import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      redirect: '/dashboard/organizations'
    },
    {
      path: '/dashboard',
      component: () => import('@/layouts/dashboard/DashboardLayout.vue'),
      children: [
        {
          path: 'organizations',
          name: 'organizations',
          component: () => import('@/views/dashboard/OrganizationsDashboard.vue')
        },
        {
          path: 'organizations/:id',
          name: 'organization-detail',
          component: () => import('@/views/dashboard/OrganizationDetail.vue')
        },
        {
          path: 'leads',
          name: 'leads',
          component: () => import('@/views/dashboard/LeadsDashboard.vue')
        },
        {
          path: 'leads/:id/conversations',
          name: 'lead-conversations',
          component: () => import('@/views/LeadConversations.vue')
        }
      ]
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/Login.vue')
    }
  ]
})

export default router
