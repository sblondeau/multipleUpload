<?php

namespace App\Twig\Components;

use App\Entity\Image;
use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\LiveArg;

#[AsLiveComponent()]
final class ProductForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    
    public string $buttonLabel = 'Save';

    #[LiveProp(fieldName:"productForm")]
    public ?Product $product = null;

    protected function instantiateForm(): FormInterface
    {
        // we can extend AbstractController to get the normal shortcuts
        return $this->createForm(ProductType::class, $this->product);
    } 
    
    #[LiveAction]
    public function save(EntityManagerInterface $entityManager, Request $request, #[Autowire('%upload_dir%')] string $uploadDir)
    {
        // Submit the form! If validation fails, an exception is thrown
        // and the component is automatically re-rendered with the errors
        $this->submitForm();

        /** @var Post $post */
        $product = $this->getForm()->getData();



        $files = $request->files->get('product')['images'] ?? [];
        foreach($files as $file) {
            if ($file instanceof UploadedFile) {
                $fileName = uniqid() . $file->getClientOriginalName();
                $file->move($uploadDir, $fileName);

                $product->addImage((new Image)->setPath($fileName));
            }
        }        

        $entityManager->persist($product);
        $entityManager->flush();    

        return $this->redirectToRoute('app_product_edit', [
            'id' => $product->getId(),
        ]);
    }

    #[LiveAction]
    public function delete(#[LiveArg()] Image $image, EntityManagerInterface $entityManager, #[Autowire('%upload_dir%')] string $uploadDir)
    {
        unlink($uploadDir.'/'.$image->getPath());
        $this->product->removeImage($image);
        $entityManager->flush();
    }
}
