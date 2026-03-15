// icons
import {
  DashboardOutlined,
  ShopOutlined,
  PhoneOutlined,
  MessageOutlined,
  UserOutlined
} from '@ant-design/icons-vue';

export interface menu {
  header?: string;
  title?: string;
  icon?: object;
  to?: string;
  divider?: boolean;
  chip?: string;
  chipColor?: string;
  chipVariant?: string;
  chipIcon?: string;
  children?: menu[];
  disabled?: boolean;
  type?: string;
  subCaption?: string;
}

const sidebarItem: menu[] = [
  { header: 'WhatsApp Multi-Tenant' },
  {
    title: 'Organizaciones',
    icon: ShopOutlined,
    to: '/dashboard/organizations',
    chip: 'new',
    chipColor: 'success',
    chipVariant: 'flat'
  },
  {
    title: 'Leads',
    icon: UserOutlined,
    to: '/dashboard/leads'
  },
  {
    title: 'Conversaciones',
    icon: MessageOutlined,
    to: '/dashboard/leads',
    subCaption: 'Chat en tiempo real'
  },
  { divider: true },
  { header: 'Configuración' },
  {
    title: 'Números WhatsApp',
    icon: PhoneOutlined,
    to: '/dashboard/organizations',
    subCaption: 'Gestionar números'
  }
];

export default sidebarItem;
