import type { Product } from '@/types';
import ProductForm from './Form';

interface Props { product: Product; }

export default function Edit({ product }: Props) {
    return <ProductForm product={product} isEdit />;
}
