import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-11 items-center justify-center rounded-2xl border border-stone-200 bg-stone-950 shadow-[0_12px_24px_-20px_rgba(15,23,42,0.34)]">
                <AppLogoIcon className="size-5 fill-current text-white" />
            </div>
            <div className="ml-2 grid flex-1 text-left">
                <span className="truncate font-['Space_Grotesk'] text-[1rem] leading-none font-bold tracking-tight text-stone-950">Throughline</span>
                <span className="truncate text-[0.68rem] font-medium tracking-[0.22em] text-stone-500 uppercase">Coach performance OS</span>
            </div>
        </>
    );
}
