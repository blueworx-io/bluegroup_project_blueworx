import ResetPasswordForm from "@/components/auth/ResetPasswordForm";

export const metadata = { title: "Choose a new password — BlueWorx" };
export const dynamic = "force-dynamic"; // uses useSearchParams

export default function Page() {
  return <main className="auth-page"><ResetPasswordForm /></main>;
}
