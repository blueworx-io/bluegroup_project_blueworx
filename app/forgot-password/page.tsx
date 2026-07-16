import ForgotPasswordForm from "@/components/auth/ForgotPasswordForm";

export const metadata = { title: "Reset your password — BlueWorx" };

export default function Page() {
  return <main className="auth-page"><ForgotPasswordForm /></main>;
}
