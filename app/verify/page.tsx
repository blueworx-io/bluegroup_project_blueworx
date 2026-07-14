import VerifyEmail from "@/components/auth/VerifyEmail";

export const metadata = { title: "Confirm your email — BlueWorx" };
export const dynamic = "force-dynamic"; // uses useSearchParams

export default function Page() {
  return <main className="auth-page"><VerifyEmail /></main>;
}
