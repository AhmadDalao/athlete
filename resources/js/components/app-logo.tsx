import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-11 items-center justify-center rounded-2xl border border-lime-300/25 bg-lime-300 shadow-[0_18px_42px_-28px_rgba(168,255,47,0.8)]">
                <AppLogoIcon className="size-5 fill-current text-[#07100c]" />
            </div>
            <div className="ml-2 grid flex-1 text-left">
                <span className="truncate font-['Space_Grotesk'] text-[1rem] leading-none font-bold tracking-tight text-stone-50">Throughline</span>
                <span className="truncate text-[0.68rem] font-medium tracking-[0.22em] text-lime-200/70 uppercase">Coach performance OS</span>
            </div>
        </>
    );
}
