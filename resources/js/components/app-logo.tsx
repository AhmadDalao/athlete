import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-11 items-center justify-center rounded-2xl border border-[#e5d2ad] bg-[#f0bd4f] shadow-[0_18px_42px_-30px_rgba(143,91,20,0.5)]">
                <AppLogoIcon className="size-5 fill-current text-stone-950" />
            </div>
            <div className="ml-2 grid flex-1 text-left">
                <span className="truncate font-['Space_Grotesk'] text-[1rem] leading-none font-bold tracking-tight text-stone-950">Throughline</span>
                <span className="truncate text-[0.68rem] font-medium tracking-[0.22em] text-[#9a6a1f] uppercase">Coach performance OS</span>
            </div>
        </>
    );
}
